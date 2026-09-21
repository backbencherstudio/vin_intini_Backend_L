<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Models\IndustryJobPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryJobPostController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $industry = $user->industry;

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any industry.',
            ], 422);
        }

        $isDraft = $request->input('action') === 'draft';

        $validated = $request->validate([
            'action'                  => ['nullable', 'in:draft,publish'],
            'job_title'               => ['required', 'string', 'max:255'],
            'job_description'         => ['required', 'string', 'max:5000'],
            'work_mode'               => ['required', 'string'],
            'employment_type'         => ['required', 'string'],

            'state'                   => [$isDraft ? 'nullable' : 'required', 'string'],
            'city'                    => [$isDraft ? 'nullable' : 'required', 'string'],
            'email'                   => ['required', 'email'],
            'phone_number'            => [$isDraft ? 'nullable' : 'required', 'string'],
            'website'                 => [$isDraft ? 'nullable' : 'required', 'url'],

            'salary_min'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'salary_max'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'gte:salary_min'],
            'employment_offering'     => [$isDraft ? 'nullable' : 'required', 'string'],

            'location_url'            => ['nullable', 'string', 'max:1000'],
            'network_type'            => ['nullable', 'string'],
            'tags'                    => ['nullable'],

            'announcement_start_date' => ['nullable', 'date'],
            'announcement_end_date'   => ['nullable', 'date', 'after_or_equal:announcement_start_date'],

            'information_confirmed'   => [$isDraft ? 'nullable' : 'accepted'],
        ]);

        $tags = $validated['tags'] ?? null;
        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        $uniqueJobId = $this->generateUniqueJobId();

        $jobPost = IndustryJobPost::create([
            'job_id'                  => $uniqueJobId,
            'industry_id'             => $industry->id,
            'created_by'              => $user->id,

            'job_title'               => $validated['job_title'],
            'job_description'         => $validated['job_description'],

            'work_mode'               => $validated['work_mode'],
            'employment_type'         => $validated['employment_type'],

            'state'                   => $validated['state'] ?? null,
            'city'                    => $validated['city'] ?? null,

            'email'                   => $validated['email'],
            'phone_number'            => $validated['phone_number'] ?? null,
            'website'                 => $validated['website'] ?? null,

            'salary_min'              => $validated['salary_min'] ?? null,
            'salary_max'              => $validated['salary_max'] ?? null,

            'location_url'            => $validated['location_url'] ?? null,
            'employment_offering'     => $validated['employment_offering'] ?? null,
            'network_type'            => $validated['network_type'] ?? null,

            'tags'                    => $tags,

            'announcement_start_date' => $validated['announcement_start_date'] ?? null,
            'announcement_end_date'   => $validated['announcement_end_date'] ?? null,

            'information_confirmed'   => $isDraft ? false : true,

            'status'                  => $isDraft
                ? IndustryJobPostStatus::DRAFT
                : IndustryJobPostStatus::PUBLISHED,

            'submitted_at'            => $isDraft ? null : now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isDraft
                ? 'Job post saved as draft successfully.'
                : 'Job post has been published successfully.',
            'data'    => $jobPost,
        ], 201);
    }

    private function generateUniqueJobId(): string
    {
        do {
            $jobId = (string) random_int(100000, 999999);
        } while (IndustryJobPost::where('job_id', $jobId)->exists());

        return $jobId;
    }
}
