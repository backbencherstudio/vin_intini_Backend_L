<?php

namespace App\Services\Advertising;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\IndustryFollow;
use App\Models\IndustryProduct;
use App\Models\User;
use App\Services\BoostSettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
    public function injectAdsIntoFeed(
        Collection $posts,
        ?User $user = null,
        ?string $networkType = null,
        bool $recordServerImpressions = false,
        ?int $maxAds = null,
        bool $includeTestAnalytics = false,
        array $excludeCampaignIds = [],
        bool $bypassFrequencyCapping = false
    ): Collection {
        $settings = $this->boostSettingService->getSettings();
        $newsfeedConfig = $settings['newsfeed_ads'] ?? [];

        $isEnabled = (bool) ($newsfeedConfig['enabled'] ?? true);
        $minPostsBeforeAd = (int) ($newsfeedConfig['min_posts_before_ad'] ?? 4);
        $firstAdSlot = (int) ($newsfeedConfig['first_ad_slot'] ?? 4);
        $adInterval = (int) ($newsfeedConfig['ad_interval'] ?? 8);
        $maxAdsPerPage = $maxAds ?? (int) ($newsfeedConfig['max_ads_per_page'] ?? 2);

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

        $excludeCampaignIds = array_values(array_filter(array_map('intval', $excludeCampaignIds)));
        $injectedInBatch = [];
        $adsInjected = 0;
        $targetSlotIndex = $firstAdSlot;

        // Dynamic Fair-Play ad insertion: 1st ad at $firstAdSlot, subsequent ads every ($adInterval) posts
        while ($items->count() >= $targetSlotIndex && $adsInjected < $maxAdsPerPage) {
            $mergedExcludes = array_merge($excludeCampaignIds, $injectedInBatch);
            $effectiveExcludes = array_values(array_reverse(array_unique(array_reverse($mergedExcludes))));

            $adCampaign = $this->rotationService->resolveAdForFeed(
                adType: 'product',
                networkType: $networkType,
                placement: 'newsfeed',
                excludeCampaignIds: $effectiveExcludes,
                userId: $user?->id,
                ipAddress: request()?->ip(),
                bypassFrequencyCapping: $bypassFrequencyCapping || $includeTestAnalytics
            );

            if ($adCampaign && $adCampaign->boostable && ! in_array($adCampaign->id, $injectedInBatch, true)) {
                $adCard = $this->formatSponsoredCard($adCampaign, $user, $includeTestAnalytics);
                if ($recordServerImpressions) {
                    $this->recordImpression($adCampaign, $user);
                }

                $items->splice($targetSlotIndex, 0, [$adCard]);
                $injectedInBatch[] = $adCampaign->id;
                $excludeCampaignIds[] = $adCampaign->id;
                $adsInjected++;
                $targetSlotIndex += ($adInterval + 1);
            } else {
                break;
            }
        }

        return $items;
    }

    /**
     * Format an ad campaign into a standardized newsfeed card.
     * Clean, flat, secure payload for production API; includes extra analytics for Blade test mode.
     *
     * @return array<string, mixed>
     */
    protected function formatSponsoredCard(AdCampaign $campaign, ?User $user = null, bool $includeTestAnalytics = false): array
    {
        $boostable = $campaign->boostable;

        if ($boostable instanceof IndustryProduct) {
            $boostable->loadMissing(['section', 'category', 'industry']);
        }

        $title = method_exists($boostable, 'getAdTitle')
            ? $boostable->getAdTitle()
            : ($boostable->product_name ?? '');

        $rawDescription = method_exists($boostable, 'getAdDescription')
            ? $boostable->getAdDescription()
            : ($boostable->description ?? null);

        // Plain text for social feed ad card: strip HTML tags and decode entities
        $captionDescription = $rawDescription
            ? trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($rawDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'))))
            : null;

        $image = method_exists($boostable, 'getAdImageUrl')
            ? $boostable->getAdImageUrl()
            : ($boostable->image_url ?? null);

        $destinationUrl = method_exists($boostable, 'getAdDestinationUrl')
            ? $boostable->getAdDestinationUrl()
            : ($boostable->product_url ?? null);

        $domain = null;
        if (! empty($destinationUrl)) {
            $parsedHost = parse_url($destinationUrl, PHP_URL_HOST);
            $domain = $parsedHost ? preg_replace('/^www\./i', '', $parsedHost) : null;
        }

        $tags = [];
        if (! empty($boostable?->tags)) {
            $tags = is_array($boostable->tags)
                ? $boostable->tags
                : (is_string($boostable->tags) ? (json_decode($boostable->tags, true) ?: []) : []);
        }

        $productName = $boostable?->product_name ?? $title;
        $productCode = $boostable?->product_id ?? null;
        $sectionName = $boostable?->section?->name ?? null;
        $categoryName = $boostable?->category?->category_name ?? null;

        $likesCount = (int) ($boostable?->likes_count ?? 0);
        $viewsCount = (int) ($boostable?->views_count ?? 0);
        $uniqueViewsCount = (int) ($boostable?->unique_views_count ?? 0);
        $isLiked = false;
        if ($user && $boostable instanceof IndustryProduct) {
            $isLiked = $boostable->likes()->where('user_id', $user->id)->exists();
        }

        $isFollowingIndustry = false;
        if ($user && $campaign->industry_id) {
            $isFollowingIndustry = IndustryFollow::where('industry_id', $campaign->industry_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $industryData = $campaign->industry ? [
            'id' => $campaign->industry_id,
            'is_following' => $isFollowingIndustry,
            'logo' => $campaign->industry->logo,
            'name' => $campaign->industry->name,
            'slug' => $campaign->industry->slug,
            'tagline' => $campaign->industry->tagline,
        ] : null;

        // 1. Clean, lightweight, industry-standard API payload (Production newsfeed)
        $card = [
            'id' => 'sponsored_ad_'.$campaign->id,
            'is_sponsored' => true,
            'ad_campaign_id' => $campaign->id,
            'ad_type' => $campaign->ad_type,
            'badge' => 'Sponsored',
            'product_id' => $boostable?->id,
            'product_code' => $productCode,
            'product_name' => $productName,
            'title' => $title,
            'description' => $captionDescription,
            'image' => $image,
            'image_url' => $image,
            'price' => $boostable?->price ?? null,
            'destination_url' => $destinationUrl,
            'destination_domain' => $domain,
            'cta_text' => 'View Product',
            'likes_count' => $likesCount,
            'views_count' => $viewsCount,
            'is_liked' => $isLiked,
            'industry' => $industryData,
            'section_name' => $sectionName,
            'category_name' => $categoryName,
            'tags' => $tags,
            'is_new_release' => (bool) ($boostable?->is_new_release ?? false),
            'created_at' => now()->toISOString(),
        ];

        // 2. Developer preview & simulation mode: include live analytics & quick-view modal details for test Blade
        if ($includeTestAnalytics) {
            $status = ucfirst($boostable?->status ?? 'active');
            $publishedAt = $boostable?->created_at ? $boostable->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A');

            $card['impressions_count'] = (int) ($campaign->total_impressions ?? 0);
            $card['clicks_count'] = (int) ($campaign->total_clicks ?? 0);
            $card['product_db_id'] = $boostable?->id;
            $card['raw_description'] = $rawDescription;
            $card['status'] = $status;
            $card['published_at'] = $publishedAt;
            $card['product_details'] = [
                'id' => $boostable?->id,
                'product_id' => $productCode,
                'product_name' => $productName,
                'description' => $rawDescription,
                'slug' => $boostable?->slug ?? null,
                'section_id' => $boostable?->section_id ?? null,
                'section_name' => $sectionName,
                'category_id' => $boostable?->category_id ?? null,
                'category_name' => $categoryName,
                'network_type' => $boostable?->network_type ?? $campaign->network_type ?? null,
                'industry_type' => $boostable?->industry_type ?? null,
                'tags' => $tags,
                'is_new_release' => (bool) ($boostable?->is_new_release ?? false),
                'status' => $status,
                'published_at' => $publishedAt,
                'likes_count' => $likesCount,
                'views_count' => $viewsCount,
                'unique_views_count' => $uniqueViewsCount,
                'is_liked' => $isLiked,
                'poc_name' => $boostable?->poc_name ?? null,
                'poc_email' => $boostable?->poc_email ?? null,
                'poc_phone' => $boostable?->poc_phone ?? null,
            ];
        }

        return $card;
    }

    /**
     * Record an ad impression and increment counters safely.
     */
    protected function recordImpression(AdCampaign $campaign, ?User $user): void
    {
        try {
            // Anti-fraud self-impression guard: Ignore impression if user is the product creator, industry creator, or campaign owner
            $isOwner = $user && (
                (int) $user->id === (int) $campaign->user_id
                || (int) $user->id === (int) ($campaign->industry?->created_by ?? 0)
                || (int) $user->id === (int) ($campaign->boostable?->creator_id ?? 0)
                || ($user->industry && (int) $user->industry->id === (int) $campaign->industry_id)
                || ($campaign->boostable instanceof IndustryProduct && $campaign->boostable->isOwnedBy($user))
            );

            if ($isOwner) {
                return;
            }

            $userIdentifier = $user ? 'user_'.$user->id : 'ip_'.(request()?->ip() ?: 'unknown');
            $throttleKey = "ad_impression_throttle_{$campaign->id}_{$userIdentifier}";

            if (! Cache::add($throttleKey, true, now()->addSeconds(30))) {
                return;
            }

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
