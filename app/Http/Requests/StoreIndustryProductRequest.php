<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndustryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['required', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['required', 'integer', 'exists:industry_categories,id'],
            'description' => ['required', 'string', 'max:5000'],
            'product_url' => ['required', 'string', 'url', 'max:1000'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'tags' => ['nullable'],
            'information_confirmed' => ['required', 'accepted'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
        ];
    }
}
