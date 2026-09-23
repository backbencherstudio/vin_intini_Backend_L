<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostSave;
use App\Models\IndustryJobPostView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndustryJobPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();

        $search = trim((string) $request->query('search', ''));
        $networkType = $request->query('network_type');
        $workMode = $request->query('work_mode');
        $employmentType = $request->query('employment_type');
        $employmentOffering = $request->query('employment_offering');
        $stateId = $request->filled('state_id') ? $request->integer('state_id') : null;
        $cityId = $request->filled('city_id') ? $request->integer('city_id') : null;

        $limit = min($request->integer('limit', 10), 100);

        $baseQuery = IndustryJobPost::query()
            ->where('status', IndustryJobPostStatus::PUBLISHED);

        $totalJobsCount = (clone $baseQuery)->count();

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%")
                    ->orWhere('job_description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($networkType)) {
            $baseQuery->where('network_type', $networkType);
        }
        if (!empty($employmentOffering)) {
            $baseQuery->where('employment_offering', $employmentOffering);
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

        $paginated = $baseQuery->with(['industry', 'state', 'city', 'creator'])
            ->latest('id')
            ->paginate($limit);

        $jobIds = $paginated->pluck('id')->all();

        $likedJobIds = ($currentUser && !empty($jobIds))
            ? IndustryJobPostLike::where('user_id', $currentUser->id)
            ->whereIn('industry_job_post_id', $jobIds)
            ->pluck('industry_job_post_id')
            ->flip()
            ->all()
            : [];

        $savedJobIds = ($currentUser && !empty($jobIds))
            ? IndustryJobPostSave::where('user_id', $currentUser->id)
            ->whereIn('industry_job_post_id', $jobIds)
            ->pluck('industry_job_post_id')
            ->flip()
            ->all()
            : [];

        $formattedData = $paginated->getCollection()->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $currentUser) {
            return $this->formatJobPost($job, $likedJobIds, $savedJobIds, $currentUser);
        })->values();

        $filters = [
            'search'              => $search !== '' ? $search : null,
            'network_type'        => $networkType ?: null,
            'employment_offering' => $employmentOffering ?: null,
            'work_mode'           => $workMode ?: null,
            'employment_type'     => $employmentType ?: null,
            'state_id'            => $stateId,
            'city_id'             => $cityId,
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

    public function myJobs(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();

        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $limit = min($request->integer('limit', 10), 100);

        $baseQuery = IndustryJobPost::query()->where('created_by', $currentUser->id);
        $totalJobsCount = (clone $baseQuery)->count();

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%")
                    ->orWhere('job_description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $baseQuery->where('status', $status);
        }

        $paginated = $baseQuery->with(['state', 'city', 'industry', 'creator'])
            ->latest('id')
            ->paginate($limit);

        $jobIds = $paginated->pluck('id')->all();

        $likedJobIds = !empty($jobIds)
            ? IndustryJobPostLike::where('user_id', $currentUser->id)
            ->whereIn('industry_job_post_id', $jobIds)
            ->pluck('industry_job_post_id')
            ->flip()
            ->all()
            : [];

        $savedJobIds = !empty($jobIds)
            ? IndustryJobPostSave::where('user_id', $currentUser->id)
            ->whereIn('industry_job_post_id', $jobIds)
            ->pluck('industry_job_post_id')
            ->flip()
            ->all()
            : [];

        $formattedData = $paginated->getCollection()->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $currentUser) {
            return $this->formatJobPost($job, $likedJobIds, $savedJobIds, $currentUser);
        })->values();

        $filters = [
            'search' => $search !== '' ? $search : null,
            'status' => $status ?: null,
        ];

        if ($paginated->isEmpty()) {
            return response()->json([
                'success'      => true,
                'message'      => $search !== '' ? 'No jobs found for this search.' : 'You have not created any job posts yet.',
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
            'message'      => 'Your job posts retrieved successfully.',
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
        $query = IndustryJobPost::with([
            'industry',
            'state',
            'city',
            'creator:id,first_name,last_name,username,profile_image'
        ]);

        if (is_numeric($identifier)) {
            $jobPost = $query->where(function ($q) use ($identifier) {
                $q->where('id', $identifier)->orWhere('job_id', (string) $identifier);
            })->first();
        } else {
            $jobPost = $query->where(function ($q) use ($identifier) {
                $q->where('slug', $identifier)->orWhere('job_id', $identifier);
            })->first();
        }

        if (!$jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found.',
            ], 404);
        }

        $currentUser = auth('api')->user();
        $isCreator = $currentUser && ((int) $jobPost->created_by === (int) $currentUser->id);

        if (!$isCreator) {
            if ($currentUser) {
                $view = IndustryJobPostView::firstOrCreate(
                    [
                        'industry_job_post_id' => $jobPost->id,
                        'user_id'              => $currentUser->id,
                    ],
                    [
                        'ip_address'           => request()->ip(),
                    ]
                );
            } else {
                $ip = request()->ip();
                $view = IndustryJobPostView::firstOrCreate(
                    [
                        'industry_job_post_id' => $jobPost->id,
                        'user_id'              => null,
                        'ip_address'           => $ip,
                    ]
                );
            }

            if ($view->wasRecentlyCreated) {
                $jobPost->increment('views_count');
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatJobPost($jobPost, null, null, $currentUser),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $industryPlan = PlanType::tryFrom('industry');
        if (!$user->hasActiveSubscription($industryPlan)) {
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

        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft';
        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? null);
        $uniqueJobId = $this->generateUniqueJobId();
        $slug = Str::slug($validated['job_title']) . '-' . $uniqueJobId;

        $startDate = $validated['announcement_start_date'] ?? $validated['start_date'] ?? null;
        $endDate   = $validated['announcement_end_date'] ?? $validated['end_date'] ?? null;

        $jobPost = IndustryJobPost::create([
            'job_id'                  => $uniqueJobId,
            'industry_id'             => $industry->id,
            'created_by'              => $user->id,
            'job_title'               => $validated['job_title'],
            'slug'                    => $slug,
            'position'                => $validated['position'] ?? null,
            'job_description'         => $validated['job_description'],
            'network_type'            => $validated['network_type'] ?? null,
            'employment_offering'     => $validated['employment_offering'] ?? null,
            'work_mode'               => $validated['work_mode'],
            'employment_type'         => $validated['employment_type'],
            'level'                   => $validated['level'] ?? null,
            'experience'              => $validated['experience'] ?? null,
            'state_id'                => $validated['state_id'] ?? null,
            'city_id'                 => $validated['city_id'] ?? null,
            'email'                   => $validated['email'],
            'phone_number'            => $validated['phone_number'] ?? null,
            'website'                 => $validated['website'] ?? null,
            'salary_min'              => $validated['salary_min'] ?? null,
            'salary_max'              => $validated['salary_max'] ?? null,
            'location_url'            => $validated['location_url'] ?? null,
            'announcement_start_date' => $startDate,
            'announcement_end_date'   => $endDate,
            'tags'                    => $tags,
            'information_confirmed'   => !$isDraft,
            'status'                  => $isDraft ? IndustryJobPostStatus::DRAFT : IndustryJobPostStatus::PUBLISHED,
            'submitted_at'            => $isDraft ? null : now(),
        ]);

        $jobPost->load(['state', 'city', 'industry', 'creator']);

        return response()->json([
            'success' => true,
            'message' => $isDraft ? 'Job post saved as draft successfully.' : 'Job post has been published successfully.',
            'data'    => $this->formatJobPost($jobPost, null, null, $user),
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

        if ((int) $jobPost->created_by !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this job post.',
            ], 403);
        }

        $industryPlan = PlanType::tryFrom('industry');
        if (!$user->hasActiveSubscription($industryPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Industry subscription plan to update a job post.',
            ], 403);
        }

        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft';
        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? $jobPost->tags);

        $slug = ($jobPost->job_title !== $validated['job_title'] || empty($jobPost->slug))
            ? Str::slug($validated['job_title']) . '-' . $jobPost->job_id
            : $jobPost->slug;

        $startDate = $validated['announcement_start_date'] ?? $validated['start_date'] ?? $jobPost->announcement_start_date;
        $endDate   = $validated['announcement_end_date'] ?? $validated['end_date'] ?? $jobPost->announcement_end_date;

        $jobPost->update([
            'slug'                    => $slug,
            'job_title'               => $validated['job_title'],
            'position'                => $validated['position'] ?? null,
            'job_description'         => $validated['job_description'],
            'work_mode'               => $validated['work_mode'],
            'employment_type'         => $validated['employment_type'],
            'level'                   => $validated['level'] ?? null,
            'experience'              => $validated['experience'] ?? null,
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
            'announcement_start_date' => $startDate,
            'announcement_end_date'   => $endDate,
            'information_confirmed'   => !$isDraft,
            'status'                  => $isDraft ? IndustryJobPostStatus::DRAFT : IndustryJobPostStatus::PUBLISHED,
            'submitted_at'            => ($jobPost->status === IndustryJobPostStatus::DRAFT && !$isDraft) ? now() : $jobPost->submitted_at,
        ]);

        $jobPost->load(['state', 'city', 'industry', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Job post updated successfully.',
            'data'    => $this->formatJobPost($jobPost, null, null, $user),
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

        if ((int) $jobPost->created_by !== (int) $user->id) {
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

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (!$jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isCreator = (int) $jobPost->created_by === (int) $user->id;

        if (!$isAdmin && !$isCreator) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update the status of this job post.',
            ], 403);
        }

        $allowedStatuses = $isAdmin
            ? 'draft,published,rejected,archive,expired'
            : 'draft,published,archive,expired';

        $validated = $request->validate([
            'status'           => ['required', 'string', "in:{$allowedStatuses}"],
            // If status is 'rejected', rejection_reason is mandatory
            'rejection_reason' => [
                'required_if:status,rejected',
                'nullable',
                'string',
                'max:1000'
            ],
        ], [
            'rejection_reason.required_if' => 'A rejection reason is required when rejecting a job post.',
        ]);

        $newStatus = $validated['status'];
        $updateData = [
            'status' => $newStatus,
        ];

        // If rejected by admin
        if ($newStatus === 'rejected') {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
            $updateData['reviewed_at'] = now();
        } elseif ($newStatus === 'published') {
            $updateData['rejection_reason'] = null;
            $updateData['reviewed_at'] = now();

            if ($jobPost->status === IndustryJobPostStatus::DRAFT) {
                $updateData['submitted_at'] = now();
                $updateData['information_confirmed'] = true;
            }
        }

        $jobPost->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Job post status updated to {$newStatus} successfully.",
            'data'    => [
                'id'               => $jobPost->id,
                'job_id'           => $jobPost->job_id,
                'status'           => $jobPost->status instanceof \BackedEnum ? $jobPost->status->value : $jobPost->status,
                'rejection_reason' => $jobPost->rejection_reason,
                'reviewed_at'      => optional($jobPost->reviewed_at)?->toDateTimeString(),
                'updated_at'       => optional($jobPost->updated_at)?->toDateTimeString(),
            ],
        ]);
    }

    public function toggleLike($id): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return DB::transaction(function () use ($id, $user) {
            $jobPost = IndustryJobPost::lockForUpdate()->findOrFail($id);
            $like = $jobPost->likes()->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();
                IndustryJobPost::where('id', $jobPost->id)
                    ->where('likes_count', '>', 0)
                    ->decrement('likes_count');
                $isLiked = false;
                $message = 'Job unliked successfully.';
            } else {
                $jobPost->likes()->create(['user_id' => $user->id]);
                $jobPost->increment('likes_count');
                $isLiked = true;
                $message = 'Job liked successfully.';
            }

            return response()->json([
                'success'     => true,
                'message'     => $message,
                'is_liked'    => $isLiked,
                'likes_count' => (int) $jobPost->fresh()->likes_count,
            ]);
        });
    }

    public function toggleSave($id): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return DB::transaction(function () use ($id, $user) {
            $jobPost = IndustryJobPost::lockForUpdate()->findOrFail($id);
            $save = $jobPost->saves()->where('user_id', $user->id)->first();

            if ($save) {
                $save->delete();
                $isSaved = false;
                $message = 'Job removed from saved list.';
            } else {
                $jobPost->saves()->create(['user_id' => $user->id]);
                $isSaved = true;
                $message = 'Job saved successfully.';
            }

            return response()->json([
                'success'  => true,
                'message'  => $message,
                'is_saved' => $isSaved,
            ]);
        });
    }

    public function savedJobs(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $limit = min($request->integer('limit', 10), 100);

        $paginated = IndustryJobPost::query()
            ->whereHas('saves', fn($q) => $q->where('user_id', $currentUser->id))
            ->with(['industry', 'state', 'city', 'creator'])
            ->latest('id')
            ->paginate($limit);

        $jobIds = $paginated->pluck('id')->all();

        $savedJobIds = !empty($jobIds) ? array_fill_keys($jobIds, true) : [];

        $likedJobIds = !empty($jobIds)
            ? IndustryJobPostLike::where('user_id', $currentUser->id)
            ->whereIn('industry_job_post_id', $jobIds)
            ->pluck('industry_job_post_id')
            ->flip()
            ->all()
            : [];

        $formattedData = $paginated->getCollection()->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $currentUser) {
            return $this->formatJobPost($job, $likedJobIds, $savedJobIds, $currentUser);
        })->values();

        return response()->json([
            'success'      => true,
            'message'      => 'Saved jobs retrieved successfully.',
            'status'       => 'success',
            'data'         => $formattedData,
            'total'        => $paginated->total(),
            'limit'        => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'total_page'   => $paginated->lastPage(),
        ]);
    }

    private function validateJobPost(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'status'                  => ['nullable', 'in:draft,published,rejected,archive,expired'],
            'job_title'               => ['required', 'string', 'max:255'],
            'position'                => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'job_description'         => ['required', 'string', 'max:5000'],
            'work_mode'               => ['required', 'string'],
            'employment_type'         => ['required', 'string'],
            'level'                   => ['nullable', 'string'],
            'experience'              => ['nullable', 'string'],

            'network_type'            => [$isDraft ? 'nullable' : 'required', 'string', 'in:psychology,neuroscience'],
            'employment_offering'     => [$isDraft ? 'nullable' : 'required', 'string', 'in:state,private,'],

            'state_id'                => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:states,id'],
            'city_id'                 => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('cities', 'id')->when($request->filled('state_id'), function ($rule) use ($request) {
                    return $rule->where('state_id', $request->input('state_id'));
                }),
            ],

            'email'                   => ['required', 'email'],
            'phone_number'            => [$isDraft ? 'nullable' : 'required', 'string'],
            'website'                 => ['nullable', 'url'],

            'salary_min'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'salary_max'              => [$isDraft ? 'nullable' : 'required', 'numeric', 'gte:salary_min'],

            'location_url'            => ['nullable', 'string', 'max:1000'],
            'tags'                    => ['nullable'],

            'start_date'              => ['nullable', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
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

    private function formatJobPost(IndustryJobPost $job, ?array $likedJobIds = null, ?array $savedJobIds = null, $currentUser = null): array
    {
        $currentUser = $currentUser ?? auth('api')->user();

        $isLiked = ($likedJobIds !== null)
            ? isset($likedJobIds[$job->id])
            : $job->isLikedBy($currentUser);

        $isSaved = ($savedJobIds !== null)
            ? isset($savedJobIds[$job->id])
            : $job->isSavedBy($currentUser);

        $logo = null;
        if ($job->industry) {
            $rawLogo = $job->industry->logo_url ?? $job->industry->logo ?? null;
            if ($rawLogo) {
                $logo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/' . ltrim($rawLogo, '/'));
            }
        }

        return [
            'id'                      => $job->id,
            'job_id'                  => $job->job_id,
            'slug'                    => $job->slug,
            'job_title'               => $job->job_title,
            'position'                => $job->position,
            'job_description'         => $job->job_description,
            'work_mode'               => $job->work_mode,
            'employment_type'         => $job->employment_type,
            'network_type'            => $job->network_type,
            'level'                   => $job->level,
            'experience'              => $job->experience,
            'employment_offering'     => $job->employment_offering,
            'email'                   => $job->email,
            'phone_number'            => $job->phone_number,
            'website'                 => $job->website,
            'salary_min'              => $job->salary_min,
            'salary_max'              => $job->salary_max,
            'location_url'            => $job->location_url,
            'tags'                    => $job->tags ?? [],
            'status'                  => $job->status instanceof \BackedEnum ? $job->status->value : $job->status,
            'information_confirmed'   => (bool) $job->information_confirmed,

            'views_count'             => (int) ($job->views_count ?? 0),
            'likes_count'             => (int) ($job->likes_count ?? 0),
            'is_liked'                => (bool) $isLiked,
            'is_saved'                => (bool) $isSaved,

            'state'                   => $job->state ? [
                'id'   => $job->state->id,
                'name' => $job->state->name,
            ] : null,
            'city'     => $job->city ? [
                'id'   => $job->city->id,
                'name' => $job->city->name,
            ] : null,
            'industry' => $job->industry ? [
                'id'   => $job->industry->id,
                'name' => $job->industry->name ?? null,
                'slug' => $job->industry->slug ?? null,
                'logo' => $logo,
            ] : null,
            'creator'           => $job->creator ? [
                'id'            => $job->creator->id,
                'name'          => trim(($job->creator->first_name ?? '') . ' ' . ($job->creator->last_name ?? '')),
                'username'      => $job->creator->username,
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
