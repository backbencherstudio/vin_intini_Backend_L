<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\NormalizesPostBaccalaureateFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostBaccalaureateProgramRequest extends FormRequest
{
    use NormalizesPostBaccalaureateFields;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePostBaccalaureateProgramInput();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'program_name' => ['sometimes', 'required', 'string', 'max:255'],
            'state_id' => ['sometimes', 'required', 'integer', 'exists:states,id'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'website' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'category_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:academia_post_baccalaureate_categories,id'],
        ];
    }
}
