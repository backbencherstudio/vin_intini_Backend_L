<?php

namespace App\Http\Requests;

use App\Enums\PlanType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicationRequest extends FormRequest
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

        // Pro Industry subscribers automatically have their publications set to 'professional'.
        // Frontend does not need to send publication_type for industry users.
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
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            // Pro Industry automatically gets 'professional'. Premium users must select from university, freelance, other.
            'publication_type' => $isIndustry
                ? ['nullable', 'string', 'in:professional']
                : ['required', 'string', 'in:university,freelance,other'],
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string', 'max:5000'],
            'authors' => ['nullable'],
            'website_url' => ['required', 'string', 'url', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'information_confirmed' => ['required', 'accepted'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
        ];
    }

    public function messages(): array
    {
        return [
            'publication_type.in' => 'The professional publications category is reserved exclusively for Pro Industry subscribers. You can select university, freelance, or other.',
            'publication_type.required' => 'Please select a publication category (university, freelance, or other).',
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

            $hasIndustryPlan = $user->hasActiveSubscription(PlanType::INDUSTRY);
            $hasPremiumPlan = $user->hasActiveSubscription(PlanType::PREMIUM);

            if (! $hasIndustryPlan && ! $hasPremiumPlan) {
                $validator->errors()->add('subscription', 'You must have an active Pro Industry or Premium subscription plan to submit a publication.');

                return;
            }

            $pubType = $this->input('publication_type');

            if ($pubType === 'professional') {
                if (! $hasIndustryPlan) {
                    $validator->errors()->add('publication_type', 'The professional publications category is reserved exclusively for Pro Industry subscribers.');
                } elseif (! $user->industry) {
                    $validator->errors()->add('industry', 'Your account is not associated with any company profile. Please create a company profile first.');
                }
            } else {
                // Category is 'university', 'freelance', or 'other'
                if (! $hasPremiumPlan && ! $hasIndustryPlan) {
                    $validator->errors()->add('publication_type', 'You must have an active subscription to post in this category.');
                }
            }
        });
    }
}
