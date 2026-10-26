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
            'Dual Placement: Marketplace Feed + Main Newsfeed',
            'Featured "Sponsored" Badge Ribbon',
            'Fair-Play Exposure Rotation (Never Buried)',
            'Real-Time Verified Views & Click Analytics',
        ];

        $packages = [
            [
                'id' => 1,
                'name' => 'Starter Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 7,
                'price' => 19.99,
                'estimated_impressions' => '5,000 – 10,000 Impressions',
                'badge' => 'Starter Tier',
                'promotion_highlight' => '7 Days Active Promotion',
                'description' => 'Essential front-row visibility to jumpstart product discovery.',
                'features' => $defaultFeatures,
                'priority_weight' => 1,
                'status' => 'active',
                'revenuecat_store_identifier_ios' => 'boost_product_7d',
                'revenuecat_store_identifier_android' => 'boost_product_7d_gp',
                'revenuecat_entitlement_identifier' => 'product_boost_7d',
            ],
            [
                'id' => 2,
                'name' => 'Growth Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 14,
                'price' => 34.99,
                'estimated_impressions' => '15,000 – 25,000 Impressions',
                'badge' => 'Most Popular Plan',
                'promotion_highlight' => '14 Days Extended Multi-Channel Reach',
                'description' => 'Multi-channel promotion across marketplace & main newsfeed.',
                'features' => $defaultFeatures,
                'priority_weight' => 2,
                'status' => 'active',
                'revenuecat_store_identifier_ios' => 'boost_product_14d',
                'revenuecat_store_identifier_android' => 'boost_product_14d_gp',
                'revenuecat_entitlement_identifier' => 'product_boost_14d',
            ],
            [
                'id' => 3,
                'name' => 'Best Value Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 30,
                'price' => 69.99,
                'estimated_impressions' => '40,000 – 60,000+ Impressions',
                'badge' => 'Best Value (Save 18%)',
                'promotion_highlight' => '30 Days Full-Month Non-Stop',
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
                ['id' => $package['id']],
                $package
            );
        }

        BoostPackage::whereNotIn('id', [1, 2, 3])->delete();
    }
}
