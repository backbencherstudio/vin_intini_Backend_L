<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['stripe_price_id' => 'price_1TzWSRBCmBVS6SSQjq672uxY'],
            [
                'name' => 'Premium Plan',
                'short_description' => 'Our premium subscription',
                'billing_rate' => 29.99,
                'billing_cycle' => 'monthly',
                'discount_percent' => 10,
                'discount_duration' => '2026-12-31',
                'badge_color' => '#FF5733',
                'status' => 'active',
                'features' => ['search_profiles', 'unlimited_direct_messaging'],
                'stripe_product_id' => 'prod_UzVTIgHM2SvZ2j',
                'stripe_price_id' => 'price_1TzWSRBCmBVS6SSQjq672uxY',
            ]
        );
    }
}
