<?php

namespace Database\Seeders;

use App\Enums\PlanFeature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['name' => 'Pro User'],
            [
                'short_description' => 'Unlock full networking, messaging, and job tools',
                'billing_rate' => 29.99,
                'billing_cycle' => 'monthly',
                'badge_color' => '#2E86DE',
                'status' => 'active',
                'stripe_price_id' => 'price_pro_user',
                'stripe_product_id' => 'prod_pro_user',
                'features' => [
                    PlanFeature::BUILD_NETWORK->value,
                    PlanFeature::COLLABORATION_GROUPS->value,
                    PlanFeature::DIRECT_MESSAGING->value,
                    PlanFeature::POSTS_ARTICLES_PHOTOS_VIDEOS->value,
                    PlanFeature::PROFILE_VIEWS_INSIGHTS->value,
                    PlanFeature::JOB_APPLICATIONS->value,
                    PlanFeature::CONNECT_ORGANIZATIONS->value,
                ],
            ]
        );

        Plan::updateOrCreate(
            ['name' => 'Pro Industries'],
            [
                'short_description' => 'Everything in Pro User plus product advertisement',
                'billing_rate' => 59.99,
                'billing_cycle' => 'monthly',
                'badge_color' => '#8E44AD',
                'status' => 'active',
                'stripe_price_id' => 'price_pro_industries',
                'stripe_product_id' => 'prod_pro_industries',
                'features' => [
                    PlanFeature::COMPANY_PROFILE->value,
                    PlanFeature::BUILD_NETWORK->value,
                    PlanFeature::COLLABORATION_GROUPS->value,
                    PlanFeature::DIRECT_MESSAGING->value,
                    PlanFeature::POSTS_ARTICLES_PHOTOS_VIDEOS->value,
                    PlanFeature::PROFILE_VIEWS_INSIGHTS->value,
                    PlanFeature::JOB_APPLICATIONS->value,
                    PlanFeature::CONNECT_ORGANIZATIONS->value,
                    PlanFeature::PRODUCT_ADVERTISEMENT->value,
                ],
            ]
        );
    }
}
