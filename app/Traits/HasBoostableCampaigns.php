<?php

namespace App\Traits;

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
}
