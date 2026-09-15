<?php

namespace App\Http\Controllers\Auth;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndustryJobPostRequest;
use App\Models\IndustryJobPost;
use Illuminate\Http\JsonResponse;

class IndustryJobPostController extends Controller
{
    public function store(StoreIndustryJobPostRequest $request): JsonResponse
    {

        $data = $request->validated();

        $user = auth()->user();

        $industry = $user->industry;

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with an industry.',
            ], 422);
        }

        $jobPost = IndustryJobPost::create([
            'industry_id' => $industry->id,

            'created_by' => $user->id,

            'job_title' => $data['job_title'],

            'job_description' => $data['job_description'],

            'work_mode' => $data['work_mode'],

            'employment_type' => $data['employment_type'],

            'state' => $data['state'] ?? null,

            'city' => $data['city'] ?? null,

            'email' => $data['email'],

            'phone_number' => $data['phone_number'] ?? null,

            'salary_min' => $data['salary_min'] ?? null,

            'salary_max' => $data['salary_max'] ?? null,

            'location_url' => $data['location_url'] ?? null,

            'employment_offering' =>
            $data['employment_offering'] ?? null,

            'tags' => $data['tags'] ?? null,

            'announcement_start_date' =>
            $data['announcement_start_date'] ?? null,

            'announcement_end_date' =>
            $data['announcement_end_date'] ?? null,

            'information_confirmed' =>
            $data['information_confirmed'],

            'status' =>
            IndustryJobPostStatus::PENDING_REVIEW,

            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Post successfully submitted and is under review.',

            'data' => $jobPost,
        ], 201);
    }
}
