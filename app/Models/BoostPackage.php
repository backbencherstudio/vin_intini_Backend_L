<?php

namespace App\Models;

use App\Enums\BoostTargetEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoostPackage extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    public const ALLOWED_PLACEMENTS = [
        'product_feed',
        'newsfeed',
    ];

    /**
     * @var array<int, string>
     */
    public const DEFAULT_PLACEMENTS = [
        'product_feed',
        'newsfeed',
    ];

    /**
     * System-guaranteed features included automatically in every boost campaign.
     *
     * @var array<int, string>
     */
    public const CORE_FEATURES = [
        'Featured "Sponsored" Badge Ribbon',
        'Fair-Play Exposure Rotation (Never Buried)',
        'Real-Time Verified Views & Click Analytics',
    ];

    protected $fillable = [
        'name',
        'target_entity',
        'placements',
        'duration_days',
        'price',
        'estimated_impressions',
        'badge',
        'promotion_highlight',
        'description',
        'features',
        'priority_weight',
        'sort_order',
        'status',
        'revenuecat_store_identifier_ios',
        'revenuecat_store_identifier_android',
        'revenuecat_entitlement_identifier',
    ];

    protected $attributes = [
        'target_entity' => 'product',
        'priority_weight' => 1,
        'sort_order' => 0,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'target_entity' => BoostTargetEntity::class,
            'placements' => 'array',
            'features' => 'array',
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'priority_weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Normalize placements into canonical allowed keys, defaulting to Dual Placement.
     *
     * @param  array<int, mixed>|null  $placements
     * @return array<int, string>
     */
    public static function normalizePlacements(?array $placements): array
    {
        if (! is_array($placements) || $placements === []) {
            return self::DEFAULT_PLACEMENTS;
        }

        $normalized = [];
        foreach (self::ALLOWED_PLACEMENTS as $allowed) {
            if (in_array($allowed, $placements, true)) {
                $normalized[] = $allowed;
            }
        }

        return $normalized !== [] ? $normalized : self::DEFAULT_PLACEMENTS;
    }

    /**
     * Build the exact user-facing features list from real enforced placements + core system features.
     *
     * @param  array<int, mixed>|null  $placements
     * @return array<int, string>
     */
    public static function buildFeaturesForPlacements(?array $placements): array
    {
        $resolved = self::normalizePlacements($placements);
        $hasProductFeed = in_array('product_feed', $resolved, true);
        $hasNewsfeed = in_array('newsfeed', $resolved, true);

        if ($hasProductFeed && $hasNewsfeed) {
            $placementFeature = 'Dual Placement: Product Feed + Main Newsfeed';
        } elseif ($hasProductFeed) {
            $placementFeature = 'Placement: Product Feed';
        } else {
            $placementFeature = 'Placement: Main Newsfeed';
        }

        return array_merge([$placementFeature], self::CORE_FEATURES);
    }

    /**
     * @return array<int, string>
     */
    public function resolvedPlacements(): array
    {
        return self::normalizePlacements($this->placements);
    }

    /**
     * @return array<int, string>
     */
    public function resolvedFeatures(): array
    {
        return self::buildFeaturesForPlacements($this->placements);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function activeCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class)->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRevenueCat(): bool
    {
        return ! empty($this->revenuecat_store_identifier_ios)
            || ! empty($this->revenuecat_store_identifier_android);
    }
}
