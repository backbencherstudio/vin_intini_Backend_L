<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndustryJobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'job_title' => [
                'required',
                'string',
                'max:255',
            ],

            'job_description' => [
                'required',
                'string',
                'max:5000',
            ],

            'work_mode' => [
                'required',
                'string',
                'max:100',
            ],

            'employment_type' => [
                'required',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'salary_min' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'salary_max' => [
                'nullable',
                'numeric',
                'gte:salary_min',
            ],

            'location_url' => [
                'nullable',
                'url',
                'max:1000',
            ],

            'employment_offering' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tags' => [
                'nullable',
                'array',
            ],

            'tags.*' => [
                'string',
                'max:100',
            ],

            'announcement_start_date' => [
                'nullable',
                'date',
            ],

            'announcement_end_date' => [
                'nullable',
                'date',
                'after_or_equal:announcement_start_date',
            ],

            'information_confirmed' => [
                'required',
                'accepted',
            ],

        ];
    }
}
