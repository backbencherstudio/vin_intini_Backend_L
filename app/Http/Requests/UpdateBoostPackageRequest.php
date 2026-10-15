<?php

namespace App\Http\Requests;

use App\Enums\BoostTargetEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoostPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'target_entity' => ['sometimes', 'required', Rule::enum(BoostTargetEntity::class)],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'estimated_impressions' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'priority_weight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'revenuecat_store_identifier_ios' => ['nullable', 'string', 'max:255'],
            'revenuecat_store_identifier_android' => ['nullable', 'string', 'max:255'],
            'revenuecat_entitlement_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }
}
