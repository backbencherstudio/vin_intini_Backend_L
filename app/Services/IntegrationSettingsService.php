<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class IntegrationSettingsService
{
    /**
     * Map integration setting keys to their config paths.
     */
    private const CONFIG_MAP = [
        'google_client_id' => 'services.google.client_id',
        'google_client_secret' => 'services.google.client_secret',
        'google_redirect_uri' => 'services.google.redirect',
        'apple_client_id' => 'services.apple.client_id',
        'apple_team_id' => 'services.apple.team_id',
        'apple_key_id' => 'services.apple.key_id',
        'apple_private_key' => 'services.apple.private_key',
        'apple_redirect_uri' => 'services.apple.redirect',
        'stripe_public_key' => 'services.stripe.key',
        'stripe_secret_key' => 'services.stripe.secret',
        'stripe_webhook_secret' => 'services.stripe.webhook_secret',
        'revenuecat_api_key' => 'revenuecat.api_key',
        'revenuecat_project_id' => 'revenuecat.project_id',
        'revenuecat_app_id' => 'revenuecat.app_id',
        'revenuecat_app_id_ios' => 'revenuecat.app_id_ios',
        'revenuecat_app_id_android' => 'revenuecat.app_id_android',
        'revenuecat_webhook_secret' => 'revenuecat.webhook_secret',
        'revenuecat_api_base_url' => 'revenuecat.base_url',
        'revenuecat_app_user_id_strategy' => 'revenuecat.app_user_id_strategy',
        'mail_mailer' => 'mail.default',
        'mail_host' => 'mail.mailers.smtp.host',
        'mail_port' => 'mail.mailers.smtp.port',
        'mail_username' => 'mail.mailers.smtp.username',
        'mail_password' => 'mail.mailers.smtp.password',
        'mail_encryption' => 'mail.mailers.smtp.encryption',
        'mail_from_address' => 'mail.from.address',
        'mail_from_name' => 'mail.from.name',
    ];

    /**
     * Settings that should be masked when returned to the admin panel.
     */
    private const SECRET_KEYS = [
        'google_client_secret',
        'apple_private_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'revenuecat_api_key',
        'revenuecat_webhook_secret',
        'mail_password',
    ];

    private const CACHE_KEY = 'integration_settings';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return IntegrationSetting::query()
                ->orderBy('section')
                ->orderBy('key')
                ->get()
                ->mapWithKeys(fn (IntegrationSetting $setting) => [$setting->key => $setting->value])
                ->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->all()[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    public function set(string $key, string $value, string $section): void
    {
        IntegrationSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'section' => $section]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Apply all stored settings on top of the loaded config values.
     * This is called at application boot so Stripe, Socialite and Mail
     * pick up the admin-configured credentials automatically. Empty or
     * null values are skipped so the config-file / .env defaults remain
     * in effect as a fallback.
     */
    public function applyOverrides(): void
    {
        if (! $this->isMigrated()) {
            return;
        }

        foreach ($this->all() as $key => $value) {
            if ($value === null || $value === '' || ! isset(self::CONFIG_MAP[$key])) {
                continue;
            }

            // The database is the single source of truth: any non-empty value
            // stored in integration_settings overrides the corresponding
            // env()-based default from the config files. Empty strings fall
            // back to the config file defaults (and thus .env) instead.
            config()->set(self::CONFIG_MAP[$key], $value);
        }
    }

    /**
     * All settings grouped by section with secrets masked.
     */
    public function maskedBySection(): array
    {
        return IntegrationSetting::query()
            ->orderBy('key')
            ->get()
            ->groupBy('section')
            ->map(fn ($settings) => $settings->map(fn (IntegrationSetting $setting) => [
                'key' => $setting->key,
                'value' => $this->mask($setting->key, $setting->value),
            ])->values())
            ->all();
    }

    public function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    private function mask(string $key, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->isSecret($key)) {
            return $value;
        }

        if (strlen($value) <= 8) {
            return '••••••••';
        }

        return substr($value, 0, 4).'••••••••'.substr($value, -4);
    }

    private function isMigrated(): bool
    {
        try {
            return Schema::hasTable('integration_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
