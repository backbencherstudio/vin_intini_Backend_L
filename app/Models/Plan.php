<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'short_description',
        'billing_rate',
        'billing_cycle',
        'discount_percent',
        'discount_duration',
        'badge_color',
        'status',
        'features',
        'stripe_product_id',
        'stripe_price_id',
        'revenuecat_product_id',
        'revenuecat_entitlement_id',
        'revenuecat_offering_id',
        'revenuecat_package_id',
        'revenuecat_store_identifier',
        'revenuecat_store_identifier_ios',
        'revenuecat_store_identifier_android',
        'revenuecat_product_id_ios',
        'revenuecat_product_id_android',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'billing_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_duration' => 'date',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
