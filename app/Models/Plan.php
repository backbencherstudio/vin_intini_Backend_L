<?php

namespace App\Models;

use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'short_description',
        'billing_rate',
        'billing_cycle',
        'plan_type',
        'discount_percent',
        'discount_duration',
        'badge_color',
        'status',
        'features',
        'stripe_product_id',
        'stripe_price_id',
        'revenuecat_store_identifier_ios',
        'revenuecat_store_identifier_android',
        'revenuecat_entitlement_identifier',
    ];

    protected $attributes = [
        'status' => 'active',
        'plan_type' => 'premium',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'billing_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_duration' => 'date',
            'plan_type' => PlanType::class,
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isRevenueCat(): bool
    {
        return ! is_null($this->revenuecat_store_identifier_ios)
            || ! is_null($this->revenuecat_store_identifier_android);
    }
}
