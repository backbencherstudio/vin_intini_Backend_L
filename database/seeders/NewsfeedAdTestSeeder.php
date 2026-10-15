<?php

namespace Database\Seeders;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class NewsfeedAdTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing Newsfeed & Sponsored Ads.
     */
    public function run(): void
    {
        $user = User::find(2) ?? User::first();
        $admin = User::find(1) ?? $user;

        // 1. Create fresh public test posts
        $testPosts = [
            'Excited to announce our groundbreaking research on neural telemetry and cognitive brain-computer interfaces. The new spatial filters boosted signal resolution significantly!',
            'Sharing insights from the European Neuropsychology Symposium: Wearable ambulatory biosensors are redefining early detection in cognitive health.',
            'Our engineering department has released a high-density EEG open-access dataset containing 128-channel motor imagery recordings.',
            'Question for neuroscience researchers: What sampling rate do you find optimal when recording ERP potentials during real-world task performance?',
            'Demonstrating real-time physiological artifact reduction using our automated EEG filtering pipeline. Results published in our latest preprint.',
            'Join our upcoming webinar on digital biomarkers and behavioral health analytics next Thursday at 3 PM UTC.',
        ];

        foreach ($testPosts as $index => $description) {
            Post::firstOrCreate(
                ['description' => $description],
                [
                    'user_id' => ($index % 2 === 0) ? $user->id : $admin->id,
                    'visibility' => 'public',
                    'who_can_comment' => 'anyone',
                    'total_like' => rand(5, 45),
                    'total_comment' => rand(1, 12),
                    'created_at' => now()->subMinutes(10 * ($index + 1)),
                ]
            );
        }

        // 2. Setup Active Boosted Products
        $product2 = IndustryProduct::find(2);
        if ($product2) {
            $product2->update([
                'status' => 'active',
                'is_boosted' => true,
                'boosted_at' => now()->subDay(),
                'boost_expires_at' => now()->addDays(13),
                'boost_score' => 100,
            ]);

            AdCampaign::updateOrCreate(
                [
                    'boostable_type' => IndustryProduct::class,
                    'boostable_id' => $product2->id,
                    'ad_type' => 'product',
                ],
                [
                    'user_id' => $product2->creator_id ?? $user->id,
                    'industry_id' => $product2->industry_id ?? 2,
                    'boost_package_id' => 2, // 14-Day Growth Boost
                    'network_type' => $product2->network_type ?? 'psychology',
                    'placements' => ['newsfeed', 'marketplace', 'search'],
                    'amount_paid' => 34.99,
                    'currency' => 'usd',
                    'payment_platform' => 'stripe',
                    'duration_days' => 14,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addDays(13),
                    'status' => AdCampaignStatus::ACTIVE,
                    'total_impressions' => 0,
                    'total_clicks' => 0,
                ]
            );
        }

        $product4 = IndustryProduct::find(4);
        if ($product4) {
            $product4->update([
                'status' => 'active',
                'image' => $product4->image ?: 'products/TpjHIl4zFQ3jUfNiYGeYhIFqaPUbIISSkuUQqoFB.webp',
                'is_boosted' => true,
                'boosted_at' => now()->subHours(2),
                'boost_expires_at' => now()->addDays(30),
                'boost_score' => 150,
            ]);

            AdCampaign::updateOrCreate(
                [
                    'boostable_type' => IndustryProduct::class,
                    'boostable_id' => $product4->id,
                    'ad_type' => 'product',
                ],
                [
                    'user_id' => $product4->creator_id ?? $admin->id,
                    'industry_id' => $product4->industry_id ?? 1,
                    'boost_package_id' => 3, // 30-Day Pro Boost
                    'network_type' => $product4->network_type ?? 'psychology',
                    'placements' => ['newsfeed', 'marketplace', 'search'],
                    'amount_paid' => 69.99,
                    'currency' => 'usd',
                    'payment_platform' => 'stripe',
                    'duration_days' => 30,
                    'starts_at' => now()->subHours(2),
                    'ends_at' => now()->addDays(30),
                    'status' => AdCampaignStatus::ACTIVE,
                    'total_impressions' => 0,
                    'total_clicks' => 0,
                ]
            );
        }
    }
}
