<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndustryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
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
            'information_confirmed' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
        ];
    }
}
