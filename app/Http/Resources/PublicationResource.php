<?php

namespace App\Http\Resources;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Publication
 */
class PublicationResource extends JsonResource
{
    /**
     * @param  Publication  $resource
     * @param  array<int, bool>|int  $likedPublicationIds
     */
    public function __construct(
        $resource,
        protected array|int $likedPublicationIds = []
    ) {
        if (is_int($likedPublicationIds)) {
            $this->likedPublicationIds = [];
        }
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isLiked = false;
        if (! empty($this->likedPublicationIds)) {
            $isLiked = isset($this->likedPublicationIds[$this->id]);
        } elseif (auth('api')->check()) {
            $isLiked = $this->likes()->where('user_id', auth('api')->id())->exists();
        }

        $user = null;
        $industry = null;

        if ($this->industry) {
            $rawLogo = $this->industry->logo ?? null;
            $logo = $rawLogo ? (str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/'.ltrim($rawLogo, '/'))) : null;
            $industry = [
                'name' => $this->industry->name,
                'image' => $logo,
            ];
        } elseif ($this->creator) {
            $userName = trim(($this->creator->first_name ?? '').' '.($this->creator->last_name ?? ''));
            $userImage = $this->creator->profile_image_url
                ?? ($this->creator->profile_image ? asset('storage/'.ltrim($this->creator->profile_image, '/')) : null);
            $user = [
                'name' => $userName ?: ($this->creator->username ?? ''),
                'image' => $userImage,
            ];
        }

        $isBoosted = method_exists($this->resource, 'isBoosted')
            ? $this->isBoosted()
            : (bool) ($this->is_boosted && ($this->boost_expires_at === null || $this->boost_expires_at->isFuture()));

        $isNewRelease = method_exists($this->resource, 'isNewRelease')
            ? $this->isNewRelease()
            : (bool) ($this->is_new_release && ($this->new_release_expires_at === null || $this->new_release_expires_at->isFuture()));

        $badge = method_exists($this->resource, 'getAdBadge')
            ? $this->getAdBadge()
            : ($isBoosted ? 'Sponsored' : ($isNewRelease ? 'New Release' : null));

        $boostStatus = method_exists($this->resource, 'getBoostStatus')
            ? $this->getBoostStatus()
            : ($isBoosted ? 'active' : 'inactive');

        $campaign = $this->relationLoaded('activeCampaign') && $this->activeCampaign
            ? $this->activeCampaign
            : ($this->relationLoaded('latestCampaign') && $this->latestCampaign
                ? $this->latestCampaign
                : ($this->relationLoaded('adCampaigns')
                    ? $this->adCampaigns->firstWhere('status', \App\Enums\AdCampaignStatus::ACTIVE)
                    : ($isBoosted && method_exists($this->resource, 'campaigns')
                        ? $this->campaigns()->where('status', \App\Enums\AdCampaignStatus::ACTIVE)->where('ends_at', '>', now())->latest('id')->first()
                        : null)));

        $boostDaysLeft = null;
        $boostRemainingText = null;

        if ($isBoosted && $this->boost_expires_at && $this->boost_expires_at->isFuture()) {
            $diffInSeconds = max(0, (int) now()->diffInSeconds($this->boost_expires_at, false));
            $boostDaysLeft = (int) ceil($diffInSeconds / 86400);

            if ($boostDaysLeft > 1) {
                $boostRemainingText = "{$boostDaysLeft} days left";
            } elseif ($diffInSeconds > 3600) {
                $diffInHours = (int) ceil($diffInSeconds / 3600);
                $boostRemainingText = "{$diffInHours} hours left";
            } else {
                $boostRemainingText = '1 hour left';
            }
        } elseif ($boostStatus === 'paused' && $campaign && $campaign->paused_at && $campaign->ends_at) {
            $diffInSeconds = max(0, (int) $campaign->paused_at->diffInSeconds($campaign->ends_at, false));
            $boostDaysLeft = (int) ceil($diffInSeconds / 86400);
            $boostRemainingText = $boostDaysLeft > 1 ? "{$boostDaysLeft} days left (paused)" : '1 day left (paused)';
        }

        return [
            'id' => $this->id,
            'publication_id' => $this->publication_id,
            'product_id' => $this->publication_id,
            'product_name' => $this->title,
            'product_image' => $this->attachment_url,
            'creator_id' => $this->creator_id,
            'user' => $user,
            'industry_id' => $this->industry_id,
            'industry' => $industry,
            'network_type' => $this->network_type,
            'publication_type' => $this->publication_type,
            'title' => $this->title,
            'slug' => $this->slug,
            'authors' => $this->authors,
            'abstract' => $this->abstract,
            'short_abstract' => Str::limit(strip_tags((string) $this->abstract), 150),
            'website_url' => $this->website_url,
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment_url,
            'file_extension' => $this->file_extension,
            'information_confirmed' => (bool) $this->information_confirmed,
            'status' => $this->status,
            'views_count' => (int) ($this->views_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'impressions_count' => (int) ($this->impressions_count ?? 0),
            'clicks_count' => (int) ($this->clicks_count ?? 0),
            'is_liked' => $isLiked,
            'is_boosted' => $isBoosted,
            'is_sponsored' => $isBoosted,
            'is_new_release' => $isNewRelease,
            'badge' => $badge,
            'boost_status' => $boostStatus,
            'boost_days_left' => $boostDaysLeft,
            'boost_remaining_text' => $boostRemainingText,
            'ad_campaign_id' => in_array($boostStatus, ['active', 'paused', 'pending']) ? $campaign?->id : null,
            'campaign' => $campaign ? [
                'id' => $campaign->id,
                'status' => $campaign->status instanceof \App\Enums\AdCampaignStatus ? $campaign->status->value : (string) $campaign->status,
                'total_impressions' => (int) $campaign->total_impressions,
                'total_clicks' => (int) $campaign->total_clicks,
                'amount_paid' => (float) $campaign->amount_paid,
                'starts_at' => $campaign->starts_at?->toISOString(),
                'ends_at' => $campaign->ends_at?->toISOString(),
            ] : null,
            'boost_expires_at' => $this->boost_expires_at?->toISOString(),
            'new_release_expires_at' => $this->new_release_expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
