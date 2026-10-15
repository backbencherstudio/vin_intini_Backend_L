<?php

namespace App\Services\Advertising;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\User;
use App\Services\BoostSettingService;
use Illuminate\Support\Collection;

class NewsfeedAdInjector
{
    public function __construct(
        protected FairPlayAdRotationService $rotationService,
        protected BoostSettingService $boostSettingService
    ) {}

    /**
     * Seamlessly weave fair-play sponsored product ads into the newsfeed timeline.
     *
     * @param  Collection<int, array<string, mixed>>  $posts
     * @return Collection<int, array<string, mixed>>
     */
    public function injectAdsIntoFeed(Collection $posts, ?User $user = null, ?string $networkType = null): Collection
    {
        $settings = $this->boostSettingService->getSettings();
        $newsfeedConfig = $settings['newsfeed_ads'] ?? [];

        $isEnabled = (bool) ($newsfeedConfig['enabled'] ?? true);
        $minPostsBeforeAd = (int) ($newsfeedConfig['min_posts_before_ad'] ?? 4);
        $firstAdSlot = (int) ($newsfeedConfig['first_ad_slot'] ?? 4);
        $adInterval = (int) ($newsfeedConfig['ad_interval'] ?? 8);
        $maxAdsPerPage = (int) ($newsfeedConfig['max_ads_per_page'] ?? 2);

        // Normalize existing posts to explicitly have is_sponsored = false for consistent API typing
        $items = $posts->map(function ($post) {
            if (is_array($post)) {
                $post['is_sponsored'] = false;
            }

            return $post;
        })->values();

        // Guard: If disabled or feed has fewer posts than minimum threshold, return untouched
        if (! $isEnabled || $items->count() < $minPostsBeforeAd) {
            return $items;
        }

        $excludeCampaignIds = [];
        $adsInjected = 0;

        // 1. First Ad Slot (e.g. index 4, after 4th post)
        if ($items->count() >= $firstAdSlot && $adsInjected < $maxAdsPerPage) {
            $adCampaign = $this->rotationService->resolveAdForFeed(
                adType: 'product',
                networkType: $networkType,
                placement: 'newsfeed',
                excludeCampaignIds: $excludeCampaignIds,
                userId: $user?->id
            );

            if ($adCampaign && $adCampaign->boostable) {
                $adCard = $this->formatSponsoredCard($adCampaign);
                $this->recordImpression($adCampaign, $user);

                $items->splice($firstAdSlot, 0, [$adCard]);
                $excludeCampaignIds[] = $adCampaign->id;
                $adsInjected++;
            }
        }

        // 2. Second Ad Slot (e.g. after next interval of posts)
        $secondSlotIndex = $firstAdSlot + 1 + $adInterval;
        if ($maxAdsPerPage >= 2 && $items->count() >= $secondSlotIndex && $adsInjected < $maxAdsPerPage) {
            $secondCampaign = $this->rotationService->resolveAdForFeed(
                adType: 'product',
                networkType: $networkType,
                placement: 'newsfeed',
                excludeCampaignIds: $excludeCampaignIds,
                userId: $user?->id
            );

            if ($secondCampaign && $secondCampaign->boostable) {
                $secondCard = $this->formatSponsoredCard($secondCampaign);
                $this->recordImpression($secondCampaign, $user);

                $items->splice($secondSlotIndex, 0, [$secondCard]);
                $excludeCampaignIds[] = $secondCampaign->id;
                $adsInjected++;
            }
        }

        return $items;
    }

    /**
     * Format an ad campaign into a standardized newsfeed card.
     *
     * @return array<string, mixed>
     */
    protected function formatSponsoredCard(AdCampaign $campaign): array
    {
        $boostable = $campaign->boostable;

        $title = method_exists($boostable, 'getAdTitle')
            ? $boostable->getAdTitle()
            : ($boostable->product_name ?? '');

        $description = method_exists($boostable, 'getAdDescription')
            ? $boostable->getAdDescription()
            : ($boostable->description ?? null);

        $image = method_exists($boostable, 'getAdImageUrl')
            ? $boostable->getAdImageUrl()
            : ($boostable->image_url ?? null);

        $destinationUrl = method_exists($boostable, 'getAdDestinationUrl')
            ? $boostable->getAdDestinationUrl()
            : ($boostable->product_url ?? null);

        return [
            'id' => 'sponsored_ad_'.$campaign->id,
            'is_sponsored' => true,
            'ad_campaign_id' => $campaign->id,
            'ad_type' => $campaign->ad_type,
            'badge' => $campaign->boostPackage?->badge ?? 'Sponsored',
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'price' => $boostable->price ?? null,
            'destination_url' => $destinationUrl,
            'cta_text' => 'View Product',
            'company' => [
                'id' => $campaign->industry_id,
                'name' => $campaign->industry?->name,
                'slug' => $campaign->industry?->slug,
                'logo' => $campaign->industry?->logo ? asset('storage/'.$campaign->industry->logo) : null,
                'tagline' => $campaign->industry?->tagline,
            ],
            'product_details' => [
                'id' => $boostable->id,
                'slug' => $boostable->slug ?? null,
                'section_id' => $boostable->section_id ?? null,
                'category_id' => $boostable->category_id ?? null,
            ],
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Record an ad impression and increment counters safely.
     */
    protected function recordImpression(AdCampaign $campaign, ?User $user): void
    {
        try {
            AdImpression::create([
                'ad_campaign_id' => $campaign->id,
                'boostable_type' => $campaign->boostable_type,
                'boostable_id' => $campaign->boostable_id,
                'user_id' => $user?->id,
                'placement' => 'newsfeed',
                'ip_address' => request()->ip(),
                'viewed_date' => today(),
            ]);

            $campaign->increment('total_impressions');

            if ($campaign->boostable && method_exists($campaign->boostable, 'increment')) {
                $campaign->boostable->increment('impressions_count');
            }
        } catch (\Throwable $e) {
            // Silently report or handle to prevent feed failure
            report($e);
        }
    }
}
