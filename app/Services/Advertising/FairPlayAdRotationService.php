<?php

namespace App\Services\Advertising;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Services\BoostSettingService;
use Illuminate\Support\Collection;

class FairPlayAdRotationService
{
    public function __construct(
        protected BoostSettingService $boostSettingService
    ) {}

    /**
     * Pick the optimal active ad campaign using Impression-Balancing Round-Robin
     * with anti-fatigue frequency capping and session deduplication.
     *
     * @param  array<int>  $excludeCampaignIds
     */
    public function resolveAdForFeed(
        string $adType = 'product',
        ?string $networkType = null,
        string $placement = 'newsfeed',
        array $excludeCampaignIds = [],
        ?int $userId = null
    ): ?AdCampaign {
        $settings = $this->boostSettingService->getSettings();
        $newsfeedConfig = $settings['newsfeed_ads'] ?? [];

        if (! ($newsfeedConfig['enabled'] ?? true)) {
            return null;
        }

        $maxDailyImpressionsPerUser = (int) ($newsfeedConfig['max_daily_impressions_per_user'] ?? 5);

        // Fetch active campaigns matching type and validity
        $query = AdCampaign::query()
            ->where('status', AdCampaignStatus::ACTIVE)
            ->where('ad_type', $adType)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->with(['boostable', 'boostPackage', 'industry:id,name,slug,logo,tagline']);

        if (! empty($excludeCampaignIds)) {
            $query->whereNotIn('id', $excludeCampaignIds);
        }

        if (! empty($networkType) && $networkType !== 'both') {
            $query->where(function ($q) use ($networkType) {
                $q->where('network_type', $networkType)
                    ->orWhere('network_type', 'both');
            });
        }

        /** @var Collection<int, AdCampaign> $campaigns */
        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            return null;
        }

        // Filter valid candidates in-memory for database engine independence
        $eligibleCampaigns = $campaigns->filter(function (AdCampaign $campaign) use ($placement, $userId, $maxDailyImpressionsPerUser) {
            // 1. Boostable entity must exist and be active
            if (! $campaign->boostable) {
                return false;
            }

            if (isset($campaign->boostable->status) && $campaign->boostable->status !== 'active') {
                return false;
            }

            // 2. Placement matching
            if (! empty($campaign->placements) && is_array($campaign->placements)) {
                if (! in_array($placement, $campaign->placements, true)) {
                    return false;
                }
            }

            // 3. User Daily Frequency Capping (Anti-Fatigue)
            if ($userId !== null && $maxDailyImpressionsPerUser > 0) {
                $todayImpressionsCount = AdImpression::where('ad_campaign_id', $campaign->id)
                    ->where('user_id', $userId)
                    ->where('viewed_date', today())
                    ->count();

                if ($todayImpressionsCount >= $maxDailyImpressionsPerUser) {
                    return false;
                }
            }

            return true;
        });

        if ($eligibleCampaigns->isEmpty()) {
            return null;
        }

        // 4. Impression-Balancing Round-Robin algorithm
        return $eligibleCampaigns->sortBy(function (AdCampaign $campaign) {
            $tierWeight = (int) ($campaign->boostPackage?->priority_weight ?? 1);
            $targetQuota = max($tierWeight * 1000, 1);
            $deliveredRatio = $campaign->total_impressions / $targetQuota;

            // Small jitter (0.0000 - 0.0100) breaks ties fairly across concurrent requests
            return $deliveredRatio + (random_int(0, 100) / 10000);
        })->first();
    }
}
