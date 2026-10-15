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
        $packages = [
            [
                'name' => '7-Day Starter Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 7,
                'price' => 19.99,
                'estimated_impressions' => '5,000 – 10,000 Views',
                'badge' => 'Starter',
                'description' => 'Ideal for weekly product launch promotions and fast reach.',
                'features' => [
                    'Marketplace Top Placement',
                    'In-Feed Sponsored Cards',
                    'Search Priority',
                    'Impression & Click Analytics',
                ],
                'priority_weight' => 1,
                'status' => 'active',
            ],
            [
                'name' => '14-Day Growth Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 14,
                'price' => 34.99,
                'estimated_impressions' => '15,000 – 25,000 Views',
                'badge' => 'Most Popular',
                'description' => 'Maximum reach and prolonged exposure across the entire network.',
                'features' => [
                    'Marketplace Top Placement',
                    'High Frequency In-Feed Cards',
                    'Search Priority',
                    'Impression & Click Analytics',
                    'Pause & Resume Flexibility',
                ],
                'priority_weight' => 2,
                'status' => 'active',
            ],
            [
                'name' => '30-Day Pro Boost',
                'target_entity' => BoostTargetEntity::PRODUCT->value,
                'duration_days' => 30,
                'price' => 69.99,
                'estimated_impressions' => '40,000 – 60,000+ Views',
                'badge' => 'Best Value',
                'description' => 'Complete platform dominance for a full month with maximum priority.',
                'features' => [
                    'Marketplace Top Placement',
                    'Highest Frequency In-Feed Cards',
                    'Top Search Ranking',
                    'Full Performance Analytics',
                    'Pause & Resume Flexibility',
                ],
                'priority_weight' => 3,
                'status' => 'active',
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
