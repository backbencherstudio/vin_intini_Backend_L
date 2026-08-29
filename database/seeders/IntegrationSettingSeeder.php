<?php

namespace Database\Seeders;

use App\Models\IntegrationSetting;
use Illuminate\Database\Seeder;

class IntegrationSettingSeeder extends Seeder
{
    /**
     * Seed the integration settings with empty defaults.
     */
    public function run(): void
    {
        $settings = [
            // Google OAuth
            'google_client_id' => 'Google OAuth',
            'google_client_secret' => 'Google OAuth',
            'google_redirect_uri' => 'Google OAuth',

            // Apple OAuth
            'apple_client_id' => 'Apple OAuth',
            'apple_team_id' => 'Apple OAuth',
            'apple_key_id' => 'Apple OAuth',
            'apple_private_key' => 'Apple OAuth',
            'apple_redirect_uri' => 'Apple OAuth',

            // Stripe
            'stripe_public_key' => 'Stripe',
            'stripe_secret_key' => 'Stripe',
            'stripe_webhook_secret' => 'Stripe',

            // RevenueCat
            'revenuecat_api_key' => 'RevenueCat',
            'revenuecat_project_id' => 'RevenueCat',
            'revenuecat_app_id' => 'RevenueCat',
            'revenuecat_app_id_ios' => 'RevenueCat',
            'revenuecat_app_id_android' => 'RevenueCat',
            'revenuecat_webhook_secret' => 'RevenueCat',
            'revenuecat_api_base_url' => 'RevenueCat',
            'revenuecat_app_user_id_strategy' => 'RevenueCat',

            // Mail
            'mail_mailer' => 'Mail',
            'mail_host' => 'Mail',
            'mail_port' => 'Mail',
            'mail_username' => 'Mail',
            'mail_password' => 'Mail',
            'mail_encryption' => 'Mail',
            'mail_from_address' => 'Mail',
            'mail_from_name' => 'Mail',
        ];

        foreach ($settings as $key => $section) {
            IntegrationSetting::updateOrCreate(
                ['key' => $key],
                ['value' => '', 'section' => $section]
            );
        }
    }
}
