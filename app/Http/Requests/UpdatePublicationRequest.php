<?php

namespace App\Http\Requests;

use App\Enums\PlanType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    protected function prepareForValidation(): void
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if ($this->filled('network_type')) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $this->input('network_type')));
            if (str_contains($rawNet, 'neuro')) {
                $this->merge(['network_type' => 'neuroscience']);
            } elseif (str_contains($rawNet, 'psych')) {
                $this->merge(['network_type' => 'psychology']);
            }
        }

        if ($user && $user->hasActiveSubscription(PlanType::INDUSTRY)) {
            $this->merge(['publication_type' => 'professional']);
        } elseif ($this->filled('publication_type')) {
            $this->merge([
                'publication_type' => strtolower(trim((string) $this->input('publication_type'))),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        $isIndustry = $user && $user->hasActiveSubscription(PlanType::INDUSTRY);

        return [
            'network_type' => ['sometimes', 'required', 'string', 'in:psychology,neuroscience'],
            'publication_type' => $isIndustry
                ? ['sometimes', 'nullable', 'string', 'in:professional']
                : ['sometimes', 'required', 'string', 'in:university,freelance,other'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'abstract' => ['sometimes', 'required', 'string', 'max:5000'],
            'authors' => ['nullable'],
            'website_url' => ['sometimes', 'required', 'string', 'url', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
        ];
    }

    public function messages(): array
    {
        return [
            'publication_type.in' => 'The professional publications category is reserved exclusively for Pro Industry subscribers. You can select university, freelance, or other.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var User|null $user */
            $user = auth('api')->user();

            if (! $user) {
                $validator->errors()->add('subscription', 'Authentication required.');

                return;
            }
        });
    }
}
