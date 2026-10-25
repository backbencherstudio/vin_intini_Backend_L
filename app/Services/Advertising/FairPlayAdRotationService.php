<?php

namespace App\Services\Advertising;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Services\BoostSettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
        ?int $userId = null,
        ?string $ipAddress = null,
        int $maxDailyImpressionsPerUser = 5,
        bool $bypassFrequencyCapping = false
    ): ?AdCampaign {
        $settings = $this->boostSettingService->getSettings();
        $newsfeedConfig = $settings['newsfeed_ads'] ?? [];

        if (! ($newsfeedConfig['enabled'] ?? true)) {
            return null;
        }

        $maxDailyImpressionsPerUser = (int) ($newsfeedConfig['max_daily_impressions_per_user'] ?? $maxDailyImpressionsPerUser);

        // Fetch active campaigns matching type and validity
        $query = AdCampaign::query()
            ->where('status', AdCampaignStatus::ACTIVE)
            ->where('ad_type', $adType)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->with(['boostable', 'boostPackage', 'industry:id,name,slug,logo,tagline']);

        if ($placement !== 'newsfeed' && ! empty($networkType) && $networkType !== 'both') {
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
        $eligibleCampaigns = $campaigns->filter(function (AdCampaign $campaign) use ($placement, $userId, $maxDailyImpressionsPerUser, $bypassFrequencyCapping) {
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
            if (! $bypassFrequencyCapping && $userId !== null && $maxDailyImpressionsPerUser > 0) {
                $todayImpressionsCount = AdImpression::where('ad_campaign_id', $campaign->id)
                    ->where('user_id', $userId)
                    ->where('placement', $placement)
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

        // Track recently served ads per user/IP so consecutive reloads rotate active campaigns smoothly
        $userKey = $userId ? "user_{$userId}" : ($ipAddress ? 'ip_'.md5($ipAddress) : null);
        $cachedRecentIds = [];
        if ($userKey) {
            $recencyKey = "user_recent_ads_{$placement}_{$adType}_{$userKey}";
            $cachedRecentIds = Cache::get($recencyKey, []);
            if (! is_array($cachedRecentIds)) {
                $cachedRecentIds = [];
            }
            if (empty($excludeCampaignIds) && ! empty($cachedRecentIds)) {
                $excludeCampaignIds = $cachedRecentIds;
            }
        }

        // 4. Stream & Session Deduplication: Prioritize unseen campaigns to prevent back-to-back duplicate ads
        $candidates = $eligibleCampaigns;
        if (! empty($excludeCampaignIds)) {
            $unseenCandidates = $eligibleCampaigns->whereNotIn('id', $excludeCampaignIds);
            if ($unseenCandidates->isNotEmpty()) {
                $candidates = $unseenCandidates;
            } elseif ($eligibleCampaigns->count() > 1) {
                // All eligible campaigns have been seen in this stream.
                // Apply sliding window exclusion: preserve chronological recency order,
                // exclude the most recently seen (N - 1) campaigns so consecutive batches rotate smoothly in round-robin.
                $uniqueRecent = array_values(array_reverse(array_unique(array_reverse($excludeCampaignIds))));
                $windowSize = max(1, $eligibleCampaigns->count() - 1);
                $recentExcluded = array_slice($uniqueRecent, -$windowSize);
                $slidingCandidates = $eligibleCampaigns->whereNotIn('id', $recentExcluded);

                if ($slidingCandidates->isNotEmpty()) {
                    $candidates = $slidingCandidates;
                } else {
                    $candidates = $eligibleCampaigns;
                }
            } else {
                $candidates = $eligibleCampaigns;
            }
        }

        // 5. Impression-Balancing Round-Robin algorithm
        $selectedCampaign = $candidates->sortBy(function (AdCampaign $campaign) {
            $tierWeight = (int) ($campaign->boostPackage?->priority_weight ?? 1);
            $targetQuota = max($tierWeight * 1000, 1);
            $deliveredRatio = $campaign->total_impressions / $targetQuota;

            // Small jitter (0.0000 - 0.0100) breaks ties fairly across concurrent requests
            return $deliveredRatio + (random_int(0, 100) / 10000);
        })->first();

        if ($selectedCampaign && $userKey) {
            $mergedRecent = array_merge($cachedRecentIds, [$selectedCampaign->id]);
            $updatedRecentIds = array_values(array_reverse(array_unique(array_reverse($mergedRecent))));
            Cache::put($recencyKey, array_slice($updatedRecentIds, -20), now()->addHours(2));
        }

        return $selectedCampaign;
    }
}
