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

            // Facebook OAuth
            'facebook_client_id' => 'Facebook OAuth',
            'facebook_client_secret' => 'Facebook OAuth',
            'facebook_redirect_uri' => 'Facebook OAuth',

            // Stripe
            'stripe_public_key' => 'Stripe',
            'stripe_secret_key' => 'Stripe',
            'stripe_webhook_secret' => 'Stripe',

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
