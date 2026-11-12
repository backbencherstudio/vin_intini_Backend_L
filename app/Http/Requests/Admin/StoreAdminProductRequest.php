<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesAdminIndustryProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAdminProductRequest extends FormRequest
{
    use ValidatesAdminIndustryProduct;

    public function authorize(): bool
    {
        return auth('api')->check();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAdminProductInputs();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['required', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['required', 'integer', 'exists:industry_categories,id'],
            'description' => ['required', 'string', 'max:5000'],
            'product_url' => ['nullable', 'string', 'url', 'max:1000'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'tags' => ['nullable'],
            'poc_name' => ['nullable', 'string', 'max:255'],
            'poc_email' => ['nullable', 'email', 'max:255'],
            'poc_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'information_confirmed' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateAdminProductRelations($validator);
    }
}

