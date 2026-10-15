<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;

class BoostSettingService
{
    public const CACHE_KEY = 'boost_system_settings';

    /**
     * Get all boosting settings merged with defaults from config/boosting.php.
     *
     * @return array{
     *     custom_slider: array{
     *         enabled: bool,
     *         channel: string,
     *         daily_rate: float,
     *         currency: string,
     *         min_days: int,
     *         max_days: int,
     *         daily_estimated_impressions_min: int,
     *         daily_estimated_impressions_max: int
     *     },
     *     new_release: array{
     *         hours: int
     *     },
     *     pause_policy: array{
     *         max_days: int,
     *         first_reminder_days: int,
     *         final_reminder_days: int
     *     }
     * }
     */
    public function getSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $dbSettings = IntegrationSetting::where('section', 'boosting')
                ->pluck('value', 'key')
                ->all();

            $sliderConfig = config('boosting.custom_slider', []);
            $pauseConfig = config('boosting.pause_policy', []);

            return [
                'custom_slider' => [
                    'enabled' => isset($dbSettings['boost_custom_slider_enabled'])
                        ? filter_var($dbSettings['boost_custom_slider_enabled'], FILTER_VALIDATE_BOOLEAN)
                        : (bool) ($sliderConfig['enabled'] ?? true),
                    'channel' => 'web_only',
                    'daily_rate' => isset($dbSettings['boost_custom_daily_rate'])
                        ? (float) $dbSettings['boost_custom_daily_rate']
                        : (float) ($sliderConfig['daily_rate'] ?? 3.00),
                    'currency' => isset($dbSettings['boost_custom_currency'])
                        ? (string) $dbSettings['boost_custom_currency']
                        : (string) ($sliderConfig['currency'] ?? 'USD'),
                    'min_days' => isset($dbSettings['boost_custom_min_days'])
                        ? (int) $dbSettings['boost_custom_min_days']
                        : (int) ($sliderConfig['min_days'] ?? 3),
                    'max_days' => isset($dbSettings['boost_custom_max_days'])
                        ? (int) $dbSettings['boost_custom_max_days']
                        : (int) ($sliderConfig['max_days'] ?? 60),
                    'daily_estimated_impressions_min' => isset($dbSettings['boost_custom_impressions_min'])
                        ? (int) $dbSettings['boost_custom_impressions_min']
                        : (int) ($sliderConfig['daily_estimated_impressions_min'] ?? 700),
                    'daily_estimated_impressions_max' => isset($dbSettings['boost_custom_impressions_max'])
                        ? (int) $dbSettings['boost_custom_impressions_max']
                        : (int) ($sliderConfig['daily_estimated_impressions_max'] ?? 1400),
                ],
                'new_release' => [
                    'hours' => isset($dbSettings['boost_new_release_hours'])
                        ? (int) $dbSettings['boost_new_release_hours']
                        : (int) config('boosting.new_release_hours', 24),
                ],
                'pause_policy' => [
                    'max_days' => isset($dbSettings['boost_pause_max_days'])
                        ? (int) $dbSettings['boost_pause_max_days']
                        : (int) ($pauseConfig['max_days'] ?? 30),
                    'first_reminder_days' => isset($dbSettings['boost_pause_first_reminder_days'])
                        ? (int) $dbSettings['boost_pause_first_reminder_days']
                        : (int) ($pauseConfig['first_reminder_days'] ?? 27),
                    'final_reminder_days' => isset($dbSettings['boost_pause_final_reminder_days'])
                        ? (int) $dbSettings['boost_pause_final_reminder_days']
                        : (int) ($pauseConfig['final_reminder_days'] ?? 29),
                ],
            ];
        });
    }

    /**
     * Update settings and invalidate cache.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(array $data): array
    {
        // Support both nested structure and flat keys
        $flatMapping = [
            'enabled' => 'boost_custom_slider_enabled',
            'daily_rate' => 'boost_custom_daily_rate',
            'currency' => 'boost_custom_currency',
            'min_days' => 'boost_custom_min_days',
            'max_days' => 'boost_custom_max_days',
            'daily_estimated_impressions_min' => 'boost_custom_impressions_min',
            'daily_estimated_impressions_max' => 'boost_custom_impressions_max',
            'new_release_hours' => 'boost_new_release_hours',
            'pause_max_days' => 'boost_pause_max_days',
            'pause_first_reminder_days' => 'boost_pause_first_reminder_days',
            'pause_final_reminder_days' => 'boost_pause_final_reminder_days',

            // Dot-notation aliases
            'custom_slider.enabled' => 'boost_custom_slider_enabled',
            'custom_slider.daily_rate' => 'boost_custom_daily_rate',
            'custom_slider.currency' => 'boost_custom_currency',
            'custom_slider.min_days' => 'boost_custom_min_days',
            'custom_slider.max_days' => 'boost_custom_max_days',
            'custom_slider.daily_estimated_impressions_min' => 'boost_custom_impressions_min',
            'custom_slider.daily_estimated_impressions_max' => 'boost_custom_impressions_max',
            'new_release.hours' => 'boost_new_release_hours',
            'pause_policy.max_days' => 'boost_pause_max_days',
            'pause_policy.first_reminder_days' => 'boost_pause_first_reminder_days',
            'pause_policy.final_reminder_days' => 'boost_pause_final_reminder_days',
        ];

        foreach ($flatMapping as $lookupKey => $dbKey) {
            $val = data_get($data, $lookupKey);
            if ($val !== null) {
                if (is_bool($val)) {
                    $val = $val ? '1' : '0';
                }
                IntegrationSetting::updateOrCreate(
                    ['key' => $dbKey],
                    ['value' => (string) $val, 'section' => 'boosting']
                );
            }
        }

        Cache::forget(self::CACHE_KEY);

        return $this->getSettings();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     channel: string,
     *     daily_rate: float,
     *     currency: string,
     *     min_days: int,
     *     max_days: int,
     *     daily_estimated_impressions_min: int,
     *     daily_estimated_impressions_max: int
     * }
     */
    public function getSliderConfig(): array
    {
        return $this->getSettings()['custom_slider'];
    }

    /**
     * @return array{
     *     max_days: int,
     *     first_reminder_days: int,
     *     final_reminder_days: int
     * }
     */
    public function getPausePolicy(): array
    {
        return $this->getSettings()['pause_policy'];
    }

    public function getNewReleaseHours(): int
    {
        return $this->getSettings()['new_release']['hours'];
    }
}
