<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Services\IntegrationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationSettingController extends Controller
{
    /**
     * All integration settings grouped by section, secrets masked.
     */
    public function index(IntegrationSettingsService $settings): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $settings->maskedBySection(),
        ], 200);
    }

    /**
     * Update integration settings (OAuth, Stripe, Mail).
     *
     * Empty values for secret fields keep the previously stored value.
     */
    public function update(Request $request, IntegrationSettingsService $settings): JsonResponse
    {
        $payload = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);

        foreach ($payload['settings'] as $key => $value) {
            $setting = IntegrationSetting::where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            // Empty secret values mean "keep the existing one".
            if ($value === null || $value === '') {
                if ($settings->isSecret($key)) {
                    continue;
                }

                $value = '';
            }

            $settings->set($key, $value, $setting->section);
        }

        return response()->json([
            'success' => true,
            'message' => 'Integration settings saved.',
        ], 200);
    }
}
