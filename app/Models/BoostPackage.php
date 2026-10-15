<?php

namespace App\Models;

use App\Enums\BoostTargetEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoostPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_entity',
        'duration_days',
        'price',
        'estimated_impressions',
        'badge',
        'description',
        'features',
        'priority_weight',
        'status',
        'revenuecat_store_identifier_ios',
        'revenuecat_store_identifier_android',
        'revenuecat_entitlement_identifier',
    ];

    protected $attributes = [
        'target_entity' => 'product',
        'priority_weight' => 1,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'target_entity' => BoostTargetEntity::class,
            'features' => 'array',
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'priority_weight' => 'integer',
        ];
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
