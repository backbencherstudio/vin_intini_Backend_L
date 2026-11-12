<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use Illuminate\Validation\Validator;

trait ValidatesAdminIndustryProduct
{
    protected function normalizeAdminProductInputs(): void
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

        if ($this->has('product_url')) {
            $trimmedUrl = trim((string) $this->input('product_url'));
            $this->merge(['product_url' => $trimmedUrl === '' ? null : $trimmedUrl]);
        }
    }

    protected function validateAdminProductRelations(Validator $validator, ?IndustryProduct $existingProduct = null): void
    {
        $validator->after(function (Validator $validator) use ($existingProduct): void {
            $industryId = $this->input('industry_id');
            if ($industryId !== null && $industryId !== '') {
                $isVerifiedIndustry = Industry::verifiedActive()
                    ->whereKey((int) $industryId)
                    ->exists();

                if (! $isVerifiedIndustry) {
                    $validator->errors()->add(
                        'industry_id',
                        'Products can only be uploaded under a verified and active industry.'
                    );
                }
            }

            $sectionId = $this->input('section_id') ?? $existingProduct?->section_id;
            $categoryId = $this->input('category_id') ?? $existingProduct?->category_id;
            $networkType = $this->input('network_type') ?? $existingProduct?->network_type;
            $industryType = $this->input('industry_type') ?? $existingProduct?->industry_type;

            if ($sectionId) {
                $section = IndustrySections::find((int) $sectionId);
                if ($section) {
                    if ($networkType && $section->network_type !== $networkType) {
                        $validator->errors()->add(
                            'section_id',
                            'The selected section does not match the chosen network type.'
                        );
                    }
                    if ($industryType && $section->industry_type !== $industryType) {
                        $validator->errors()->add(
                            'section_id',
                            'The selected section does not match the chosen industry type.'
                        );
                    }
                }
            }

            if ($categoryId && $sectionId) {
                $category = IndustryCategory::find((int) $categoryId);
                if ($category && (int) $category->section_id !== (int) $sectionId) {
                    $validator->errors()->add(
                        'category_id',
                        'The selected category does not belong to the selected section.'
                    );
                }
            }
        });
    }
}

