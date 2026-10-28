<?php

namespace App\Traits;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasBoostableCampaigns
{
    public function campaigns(): MorphMany
    {
        return $this->morphMany(AdCampaign::class, 'boostable');
    }

    public function activeCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function latestCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->latestOfMany();
    }

    public function getBoostStatus(): string
    {
        if ($this->isBoosted()) {
            return 'active';
        }

        $campaign = $this->relationLoaded('latestCampaign')
            ? $this->latestCampaign
            : ($this->relationLoaded('activeCampaign') && $this->activeCampaign ? $this->activeCampaign : $this->campaigns()->latest('id')->first());

        if (! $campaign) {
            return 'inactive';
        }

        $status = $campaign->status instanceof AdCampaignStatus
            ? $campaign->status->value
            : (string) $campaign->status;

        if ($status === 'paused') {
            return 'paused';
        }

        if ($status === 'active' && ($campaign->ends_at === null || $campaign->ends_at->isFuture())) {
            return 'active';
        }

        if ($status === 'completed' || ($campaign->ends_at && $campaign->ends_at->isPast())) {
            return 'completed';
        }

        if ($status === 'cancelled') {
            return 'cancelled';
        }

        if ($status === 'pending') {
            return 'pending';
        }

        return 'inactive';
    }

    public function isBoosted(): bool
    {
        return (bool) $this->is_boosted && ($this->boost_expires_at === null || $this->boost_expires_at->isFuture());
    }

    public function scopeBoosted(Builder $query): Builder
    {
        return $query->where('is_boosted', true)
            ->where('boost_expires_at', '>', now());
    }

    public function scopeNewRelease(Builder $query): Builder
    {
        return $query->where('is_new_release', true)
            ->where('new_release_expires_at', '>', now());
    }

    public function isNewRelease(): bool
    {
        return (bool) $this->is_new_release && ($this->new_release_expires_at === null || $this->new_release_expires_at->isFuture());
    }

    public function getAdBadge(): ?string
    {
        if ($this->isBoosted()) {
            return 'Sponsored';
        }

        if ($this->isNewRelease()) {
            return 'New Release';
        }

        return null;
    }

    public function scopeFeedOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_boosted')
            ->orderBy('impressions_count', 'asc')
            ->orderByDesc('is_new_release')
            ->orderByDesc('id');
    }
}
