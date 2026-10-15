<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBoostSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Flat custom slider parameters
            'enabled' => ['nullable', 'boolean'],
            'daily_rate' => ['nullable', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'min_days' => ['nullable', 'integer', 'min:1'],
            'max_days' => ['nullable', 'integer', 'min:1'],
            'daily_estimated_impressions_min' => ['nullable', 'integer', 'min:1'],
            'daily_estimated_impressions_max' => ['nullable', 'integer', 'min:1'],

            // Nested custom slider parameters
            'custom_slider' => ['nullable', 'array'],
            'custom_slider.enabled' => ['nullable', 'boolean'],
            'custom_slider.daily_rate' => ['nullable', 'numeric', 'min:0.01'],
            'custom_slider.currency' => ['nullable', 'string', 'max:10'],
            'custom_slider.min_days' => ['nullable', 'integer', 'min:1'],
            'custom_slider.max_days' => ['nullable', 'integer', 'min:1'],
            'custom_slider.daily_estimated_impressions_min' => ['nullable', 'integer', 'min:1'],
            'custom_slider.daily_estimated_impressions_max' => ['nullable', 'integer', 'min:1'],

            // New release parameters
            'new_release_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            'new_release' => ['nullable', 'array'],
            'new_release.hours' => ['nullable', 'integer', 'min:0', 'max:720'],

            // Pause policy parameters
            'pause_max_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_first_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_final_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_policy' => ['nullable', 'array'],
            'pause_policy.max_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_policy.first_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_policy.final_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
