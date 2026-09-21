<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Models\IndustryJobPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndustryJobPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $networkType = $request->query('network_type');
        $workMode = $request->query('work_mode');
        $employmentType = $request->query('employment_type');
        $stateId = $request->filled('state_id') ? $request->integer('state_id') : null;
        $cityId = $request->filled('city_id') ? $request->integer('city_id') : null;

        $limit = $request->integer('limit', 10);
        if ($limit > 100) {
            $limit = 100;
        }

        $baseQuery = IndustryJobPost::query()
            ->where('status', IndustryJobPostStatus::PUBLISHED);

        $totalJobsCount = (clone $baseQuery)->count();

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($networkType)) {
            $baseQuery->where('network_type', $networkType);
        }
        if (!empty($workMode)) {
            $baseQuery->where('work_mode', $workMode);
        }
        if (!empty($employmentType)) {
            $baseQuery->where('employment_type', $employmentType);
        }
        if (!empty($stateId)) {
            $baseQuery->where('state_id', $stateId);
        }
        if (!empty($cityId)) {
            $baseQuery->where('city_id', $cityId);
        }

        $paginated = $baseQuery->with(['industry', 'state', 'city'])
            ->latest('id')
            ->paginate($limit);

        $formattedData = $paginated->getCollection()->map(function (IndustryJobPost $job) {
            return $this->formatJobPost($job);
        })->values();

        $filters = [
            'search'          => $search !== '' ? $search : null,
            'network_type'    => $networkType ?: null,
            'work_mode'       => $workMode ?: null,
            'employment_type' => $employmentType ?: null,
            'state_id'        => $stateId,
            'city_id'         => $cityId,
        ];

        if ($paginated->isEmpty()) {
            return response()->json([
                'success'      => true,
                'message'      => $search !== '' ? 'No job posts found for this search.' : 'No job posts available.',
                'status'       => 'success',
                'total_jobs'   => $totalJobsCount,
                'data'         => [],
                'stats'        => [
                    'total_jobs'    => $totalJobsCount,
                    'filtered_jobs' => 0,
                ],
                'total'        => 0,
                'limit'        => $limit,
                'current_page' => $paginated->currentPage(),
                'total_page'   => 0,
                'last_page'    => 0,
                'filters'      => $filters,
            ], 200);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Job posts retrieved successfully.',
            'status'       => 'success',
            'total_jobs'   => $totalJobsCount,
            'data'         => $formattedData,
            'stats'        => [
                'total_jobs'    => $totalJobsCount,
                'filtered_jobs' => $paginated->total(),
            ],
            'total'        => $paginated->total(),
            'limit'        => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'total_page'   => $paginated->lastPage(),
            'last_page'    => $paginated->lastPage(),
            'filters'      => $filters,
        ], 200);
    }

    public function show($identifier): JsonResponse
    {
        $jobPost = IndustryJobPost::with([
            'industry',
            'state',
            'city',
            'creator:id,first_name,last_name,email,profile_image'
        ])
            ->where('id', $identifier)
            ->orWhere('job_id', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (!$jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatJobPost($jobPost),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $industryPlan = PlanType::tryFrom('industry');
        if (! $user->hasActiveSubscription($industryPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Industry subscription plan to create a job post.',
            ], 403);
        }

        $industry = $user->industry;
        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any industry.',
            ], 422);
        }

        $isDraft = $request->input('action') === 'draft';

        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? null);
        $uniqueJobId = $this->generateUniqueJobId();

        $slug = Str::slug($validated['job_title']) . '-' . $uniqueJobId;

        $jobPost = IndustryJobPost::create([
            'job_id'                  => $uniqueJobId,
            'slug'                    => $slug,
            'industry_id'             => $industry->id,
            'created_by'              => $user->id,

            'job_title'               => $validated['job_title'],
            'job_description'         => $validated['job_description'],

            'work_mode'               => $validated['work_mode'],
            'employment_type'         => $validated['employment_type'],

            'state_id'                => $validated['state_id'] ?? null,
            'city_id'                 => $validated['city_id'] ?? null,

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

        $jobPost->load(['state', 'city', 'industry']);

        return response()->json([
            'success' => true,
            'message' => $isDraft
                ? 'Job post saved as draft successfully.'
                : 'Job post has been published successfully.',
            'data'    => $this->formatJobPost($jobPost),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (!$jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        if ($jobPost->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this job post.',
            ], 403);
        }

        $industryPlan = PlanType::tryFrom('industry');
        if (! $user->hasActiveSubscription($industryPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Industry subscription plan to update a job post.',
            ], 403);
        }

        $isDraft = $request->input('action') === 'draft';

        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? $jobPost->tags);

        $slug = ($jobPost->job_title !== $validated['job_title'])
            ? Str::slug($validated['job_title']) . '-' . $jobPost->job_id
            : $jobPost->slug;

        $jobPost->update([
            'slug'                    => $slug,
            'job_title'               => $validated['job_title'],
            'job_description'         => $validated['job_description'],

            'work_mode'               => $validated['work_mode'],
            'employment_type'         => $validated['employment_type'],

            'state_id'                => $validated['state_id'] ?? null,
            'city_id'                 => $validated['city_id'] ?? null,

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

            'submitted_at'            => ($jobPost->status === IndustryJobPostStatus::DRAFT && !$isDraft)
                ? now()
                : $jobPost->submitted_at,
        ]);

        $jobPost->load(['state', 'city', 'industry']);

        return response()->json([
            'success' => true,
            'message' => 'Job post updated successfully.',
            'data'    => $this->formatJobPost($jobPost),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (!$jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        if ($jobPost->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this job post.',
            ], 403);
        }

        $jobPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job post deleted successfully.',
        ]);
    }

    private function validateJobPost(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'action'                  => ['nullable', 'in:draft,publish'],
            'job_title'               => ['required', 'string', 'max:255'],
            'job_description'         => ['required', 'string', 'max:5000'],
            'work_mode'               => ['required', 'string'],
            'employment_type'         => ['required', 'string'],

            'state_id'                => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:states,id'],
            'city_id'                 => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('cities', 'id')->where(function ($query) use ($request) {
                    return $query->where('state_id', $request->input('state_id'));
                }),
            ],

            'email'                   => ['required', 'email'],
            'phone_number'            => [$isDraft ? 'nullable' : 'required', 'string'],
            'website'                 => [$isDraft ? 'nullable' : 'required', 'url'],

            'salary_min'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'salary_max'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'gte:salary_min'],
            'employment_offering'     => [$isDraft ? 'nullable' : 'required', 'string'],

            'location_url'            => ['nullable', 'string', 'max:1000'],

            'network_type'            => [$isDraft ? 'nullable' : 'required', 'string', 'in:psychology,neuroscience'],

            'tags'                    => ['nullable'],

            'announcement_start_date' => ['nullable', 'date'],
            'announcement_end_date'   => ['nullable', 'date', 'after_or_equal:announcement_start_date'],

            'information_confirmed'   => [$isDraft ? 'nullable' : 'accepted'],
        ]);
    }

    private function formatTags(mixed $tags): ?array
    {
        if (is_string($tags)) {
            return array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        return is_array($tags) ? $tags : null;
    }

    private function generateUniqueJobId(): string
    {
        do {
            $jobId = (string) random_int(100000, 999999);
        } while (IndustryJobPost::where('job_id', $jobId)->exists());

        return $jobId;
    }

    private function formatJobPost(IndustryJobPost $job): array
    {
        return [
            'id'                      => $job->id,
            'job_id'                  => $job->job_id,
            'slug'                    => $job->slug,
            'job_title'               => $job->job_title,
            'job_description'         => $job->job_description,
            'work_mode'               => $job->work_mode,
            'employment_type'         => $job->employment_type,
            'network_type'            => $job->network_type,
            'email'                   => $job->email,
            'phone_number'            => $job->phone_number,
            'website'                 => $job->website,
            'salary_min'              => $job->salary_min,
            'salary_max'              => $job->salary_max,
            'location_url'            => $job->location_url,
            'employment_offering'     => $job->employment_offering,
            'tags'                    => $job->tags ?? [],
            'status'                  => $job->status instanceof \BackedEnum ? $job->status->value : $job->status,
            'information_confirmed'   => (bool) $job->information_confirmed,
            'state'                   => $job->state ? [
                'id'   => $job->state->id,
                'name' => $job->state->name,
            ] : null,
            'city'                    => $job->city ? [
                'id'   => $job->city->id,
                'name' => $job->city->name,
            ] : null,
            'industry'                => $job->industry ? [
                'id'   => $job->industry->id,
                'name' => $job->industry->name ?? null,
            ] : null,
            'creator' => $job->creator ? [
                'id'            => $job->creator->id,
                'first_name'    => $job->creator->first_name,
                'last_name'     => $job->creator->last_name,
                'profile_image' => $job->creator->profile_image_url ?? $job->creator->profile_image,
            ] : null,
            'announcement_start_date' => optional($job->announcement_start_date)?->toDateString(),
            'announcement_end_date'   => optional($job->announcement_end_date)?->toDateString(),
            'submitted_at'            => optional($job->submitted_at)?->toDateTimeString(),
            'reviewed_at'             => optional($job->reviewed_at)?->toDateTimeString(),
            'rejection_reason'        => $job->rejection_reason,
            'created_at'              => optional($job->created_at)?->toDateTimeString(),
        ];
    }
}
