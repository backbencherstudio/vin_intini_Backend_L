<?php

namespace App\Services\Advertising;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;

class ProductBoostProjection
{
    /**
     * Recalculate and project the boosted state, expiration, and priority score
     * of an IndustryProduct from all of its eligible active campaigns.
     */
    public static function projectFor(?IndustryProduct $product): void
    {
        if (! $product instanceof IndustryProduct) {
            return;
        }

        $now = now();

        $activeCampaigns = AdCampaign::where('boostable_type', IndustryProduct::class)
            ->where('boostable_id', $product->id)
            ->where('status', AdCampaignStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $now)
            ->with('boostPackage')
            ->get();

        if ($activeCampaigns->isEmpty()) {
            $product->update([
                'is_boosted' => false,
                'boost_expires_at' => null,
                'boost_score' => 0,
            ]);

            return;
        }

        $maxEndsAt = $activeCampaigns->max('ends_at');
        $maxScore = $activeCampaigns->map(fn ($campaign) => $campaign->boostPackage?->priority_weight ?? 1)->max();

        $product->update([
            'is_boosted' => true,
            'boost_expires_at' => $maxEndsAt,
            'boost_score' => $maxScore,
        ]);
    }
}
