<?php

namespace Database\Seeders;

use App\Enums\BoostTargetEntity;
use App\Models\BoostPackage;
use Illuminate\Database\Seeder;

class BoostPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultFeatures = [
            'Marketplace Feed + Main Newsfeed',
            'Sponsored Badge',
            'Fair-Play',
            'Live Analytics',
        ];

        $packages = [
            [
                'name' => 'Starter Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 7,
                'price' => 19.99,
                'estimated_impressions' => '5,000 – 10,000 Impressions',
                'badge' => 'Starter Tier',
                'description' => 'Essential front-row visibility to jumpstart product discovery.',
                'features' => $defaultFeatures,
                'priority_weight' => 1,
                'status' => 'active',
                'revenuecat_store_identifier_ios' => 'boost_product_7d',
                'revenuecat_store_identifier_android' => 'boost_product_7d_gp',
                'revenuecat_entitlement_identifier' => 'product_boost_7d',
            ],
            [
                'name' => 'Growth Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 14,
                'price' => 34.99,
                'estimated_impressions' => '15,000 – 25,000 Impressions',
                'badge' => 'Most Popular Plan',
                'description' => 'Multi-channel promotion across marketplace & main newsfeed.',
                'features' => $defaultFeatures,
                'priority_weight' => 2,
                'status' => 'active',
                'revenuecat_store_identifier_ios' => 'boost_product_14d',
                'revenuecat_store_identifier_android' => 'boost_product_14d_gp',
                'revenuecat_entitlement_identifier' => 'product_boost_14d',
            ],
            [
                'name' => 'Best Value Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 30,
                'price' => 69.99,
                'estimated_impressions' => '40,000 – 60,000+ Impressions',
                'badge' => 'Best Value (Save 18%)',
                'description' => 'Full-month omnipresent promotion for top vendors & brands.',
                'features' => $defaultFeatures,
                'priority_weight' => 3,
                'status' => 'active',
                'revenuecat_store_identifier_ios' => 'boost_product_30d',
                'revenuecat_store_identifier_android' => 'boost_product_30d_gp',
                'revenuecat_entitlement_identifier' => 'product_boost_30d',
            ],
        ];

        foreach ($packages as $package) {
            BoostPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
