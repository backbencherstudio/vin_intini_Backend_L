<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndustryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('network_type')) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $this->input('network_type')));
            if (str_contains($rawNet, 'neuro')) {
                $this->merge(['network_type' => 'neuroscience']);
            } elseif (str_contains($rawNet, 'psych')) {
                $this->merge(['network_type' => 'psychology']);
            }
        }

        if ($this->filled('industry_type')) {
            $rawInd = strtolower(trim((string) $this->input('industry_type')));
            if ($rawInd === 'biotech') {
                $this->merge(['industry_type' => 'biotechnology']);
            }
        }
    }

    public function rules(): array
    {
        return [
            'product_name' => ['sometimes', 'required', 'string', 'max:255'],
            'network_type' => ['sometimes', 'required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['sometimes', 'required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['sometimes', 'required', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:industry_categories,id'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'product_url' => ['sometimes', 'required', 'string', 'url', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'tags' => ['nullable'],
            'poc_name' => ['nullable', 'string', 'max:255'],
            'poc_email' => ['nullable', 'email', 'max:255'],
            'poc_phone' => ['nullable', 'string', 'max:50'],
            'information_confirmed' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
        ];
    }
}
