<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
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
        $cityParam = $this->route('city');
        $cityId = $cityParam instanceof City ? $cityParam->id : (int) $cityParam;

        return [
            'state_id' => ['sometimes', 'required', 'integer', 'exists:states,id'],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('cities', 'name')->where(function ($query) use ($cityParam) {
                    $stateId = $this->input('state_id')
                        ?? ($cityParam instanceof City ? $cityParam->state_id : City::where('id', (int) $cityParam)->value('state_id'));

                    if ($stateId) {
                        $query->where('state_id', $stateId);
                    }

                    return $query;
                })->ignore($cityId),
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
