<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('cities', 'name')->where(function ($query) {
                    return $query->where('state_id', $this->input('state_id'));
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'state_id.required' => 'Please select a state for the city.',
            'state_id.exists' => 'The selected state is invalid.',
            'name.required' => 'The city name is required.',
            'name.unique' => 'A city with this name already exists in the selected state.',
        ];
    }
}
