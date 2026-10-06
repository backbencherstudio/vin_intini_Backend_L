<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryJobCardResource;
use App\Http\Resources\IndustryJobPostResource;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostView;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndustryJobPostController extends Controller
{
    /**
     * Industry job post list for the authenticated creator (excluding archive).
     */
    public function myJobs(Request $request): JsonResponse
    {
        return $this->getCreatorJobs($request, false);
    }

    /**
     * Industry job post list for the authenticated creator (archived only).
     */
    public function myArchivedJobs(Request $request): JsonResponse
    {
        return $this->getCreatorJobs($request, true);
    }

    /**
     * Shared query and response builder for creator's active and archived jobs.
     */
    private function getCreatorJobs(Request $request, bool $isArchived): JsonResponse
    {
        $currentUser = auth('api')->user();

        if (! $currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobId = trim((string) $request->query('job_id', ''));
        $search = trim((string) $request->query('search', ''));
        $rawStatus = $request->query('status');
        $networkType = $request->query('network_type');
        $workMode = $request->query('work_mode');
        $employmentOffering = $request->query('employment_offering');
        $employmentType = $request->query('employment_type');
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $stateId = $request->filled('state_id') ? $request->integer('state_id') : null;
        $cityId = $request->filled('city_id') ? $request->integer('city_id') : null;

        $page = max($request->integer('current_page', 1), 1);
        $limit = min(max($request->integer('limit', 10), 1), 100);

        // JIT auto-sync: Automatically update past-deadline published jobs to expired in DB
        IndustryJobPost::where('created_by', $currentUser->id)
            ->where('status', IndustryJobPostStatus::PUBLISHED->value)
            ->whereNotNull('announcement_end_date')
            ->whereDate('announcement_end_date', '<', today())
            ->update(['status' => IndustryJobPostStatus::EXPIRED]);

        $baseQuery = IndustryJobPost::query()->where('created_by', $currentUser->id);

        if ($isArchived) {
            $baseQuery->where('status', IndustryJobPostStatus::ARCHIVE->value);
        } else {
            $baseQuery->where('status', '!=', IndustryJobPostStatus::ARCHIVE->value);
        }

        $totalJobsCount = (clone $baseQuery)->count();

        // 1. Specific Job ID Filter
        if ($jobId !== '') {
            $baseQuery->where('job_id', $jobId);
        }

        // 2. Global Text Search
        if ($search !== '') {
            $baseQuery->where(function (Builder $q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', $search)
                    ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        // 3. Status Filters (only for active jobs)
        $statuses = [];
        if (! $isArchived && ! empty($rawStatus)) {
            $statuses = is_array($rawStatus)
                ? array_filter(array_map('trim', $rawStatus))
                : array_filter(array_map('trim', explode(',', (string) $rawStatus)));

            if (! empty($statuses)) {
                $baseQuery->whereIn('status', $statuses);
            }
        }

        // 4. Dropdown / Attribute Filters
        if (! empty($networkType)) {
            $baseQuery->where('network_type', $networkType);
        }
        if (! empty($workMode)) {
            $baseQuery->where('work_mode', $workMode);
        }
        if (! empty($employmentOffering)) {
            $baseQuery->where('employment_offering', $employmentOffering);
        }
        if (! empty($employmentType)) {
            $baseQuery->where('employment_type', $employmentType);
        }
        if (! empty($category)) {
            is_array($category) ? $baseQuery->whereIn('category', $category) : $baseQuery->where('category', $category);
        }
        if (! empty($subCategory)) {
            is_array($subCategory) ? $baseQuery->whereIn('sub_category', $subCategory) : $baseQuery->where('sub_category', $subCategory);
        }

        // 5. Location Filters
        if (! empty($stateId)) {
            $baseQuery->where('state_id', $stateId);
        }
        if (! empty($cityId)) {
            $baseQuery->where('city_id', $cityId);
        }

        $paginated = $baseQuery->with([
            'industry:id,name,logo',
            'state:id,name',
            'city:id,name',
        ])
            ->withCount('applications')
            ->latest('id')
            ->paginate($limit, ['*'], 'page', $page);

        $formattedData = IndustryJobCardResource::collection($paginated->getCollection())->resolve();

        $filters = [
            'job_id' => $jobId !== '' ? $jobId : null,
            'search' => $search !== '' ? $search : null,
            'network_type' => $networkType ?: null,
            'work_mode' => $workMode ?: null,
            'employment_offering' => $employmentOffering ?: null,
            'employment_type' => $employmentType ?: null,
            'category' => $category ?: null,
            'sub_category' => $subCategory ?: null,
            'state_id' => $stateId,
            'city_id' => $cityId,
        ];

        if (! $isArchived) {
            $filters['status'] = ! empty($statuses) ? (count($statuses) === 1 ? reset($statuses) : $statuses) : null;
        }

        $emptyMessage = $isArchived ? 'No archived jobs found.' : 'No jobs found matching your criteria.';
        $successMessage = $isArchived ? 'Archived job posts retrieved successfully.' : 'Your job posts retrieved successfully.';

        return response()->json([
            'success' => true,
            'message' => $paginated->isEmpty() ? $emptyMessage : $successMessage,
            'status' => 'success',
            'total_jobs' => $totalJobsCount,
            'data' => $formattedData,
            'stats' => [
                'total_jobs' => $totalJobsCount,
                'filtered_jobs' => $paginated->total(),
            ],
            'total' => $paginated->total(),
            'limit' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'total_page' => $paginated->lastPage(),
            'last_page' => $paginated->lastPage(),
            'filters' => $filters,
        ], 200);
    }

    /**
     * Display a specific job post details.
     */
    public function show(string|int $identifier): JsonResponse
    {
        $query = IndustryJobPost::with([
            'industry',
            'state:id,name',
            'city:id,name',
            'creator:id,first_name,last_name,username,profile_image',
        ])->withCount('applications');

        if (is_numeric($identifier)) {
            $jobPost = $query->where(function (Builder $q) use ($identifier) {
                $q->where('id', (int) $identifier)->orWhere('job_id', (string) $identifier);
            })->first();
        } else {
            $jobPost = $query->where(function (Builder $q) use ($identifier) {
                $q->where('slug', (string) $identifier)->orWhere('job_id', (string) $identifier);
            })->first();
        }

        if (! $jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found.',
            ], 404);
        }

        $currentUser = auth('api')->user();
        $isCreator = $currentUser && ((int) $jobPost->created_by === (int) $currentUser->id);
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && ($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin'));

        // JIT auto-sync: If deadline has passed, update DB status to expired
        $currentStatusEnum = $jobPost->status instanceof \BackedEnum ? $jobPost->status : IndustryJobPostStatus::tryFrom((string) $jobPost->status);
        if ($currentStatusEnum === IndustryJobPostStatus::PUBLISHED
            && $jobPost->announcement_end_date
            && $jobPost->announcement_end_date->isPast()) {
            $jobPost->update(['status' => IndustryJobPostStatus::EXPIRED]);
            $jobPost->refresh();
        }

        $currentStatus = $jobPost->status instanceof \BackedEnum ? $jobPost->status->value : (string) $jobPost->status;

        // Security check: Only creators and admins can view private/draft/rejected jobs.
        // Published, Expired, and Archived jobs can be viewed (e.g. by users who saved or applied).
        $allowedPublicStatuses = [
            IndustryJobPostStatus::PUBLISHED->value,
            IndustryJobPostStatus::EXPIRED->value,
            IndustryJobPostStatus::ARCHIVE->value,
        ];

        if (! $isCreator && ! $isAdmin && ! in_array($currentStatus, $allowedPublicStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found.',
            ], 404);
        }

        // Increment unique views (excluding creator)
        if (! $isCreator) {
            $ip = request()->ip();

            $viewCriteria = [
                'industry_job_post_id' => $jobPost->id,
                'user_id' => $currentUser?->id,
            ];

            if (! $currentUser) {
                $viewCriteria['ip_address'] = $ip;
            }

            $view = IndustryJobPostView::firstOrCreate(
                $viewCriteria,
                ['ip_address' => $ip]
            );

            if ($view->wasRecentlyCreated) {
                $jobPost->increment('views_count');
            }
        }

        return response()->json([
            'success' => true,
            'data' => (new IndustryJobPostResource($jobPost, currentUser: $currentUser))->resolve(),
        ]);
    }

    /**
     * Store a new industry job post.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        // Verify active Pro Industry subscription & company profile
        if ($accessError = $this->validateIndustryAccess($user)) {
            return $accessError;
        }

        $industry = $user->industry;

        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft';
        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? null);
        $uniqueJobId = $this->generateUniqueJobId((int) $industry->id);
        $slug = Str::slug($validated['job_title']).'-'.$uniqueJobId;

        $startDate = $validated['announcement_start_date'] ?? $validated['start_date'] ?? null;
        $endDate = $validated['announcement_end_date'] ?? $validated['end_date'] ?? null;

        $jobPost = IndustryJobPost::create([
            'job_id' => $uniqueJobId,
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => $validated['job_title'],
            'slug' => $slug,
            'position' => $validated['position'] ?? null,
            'category' => $validated['category'] ?? null,
            'sub_category' => $validated['sub_category'] ?? null,
            'job_description' => $validated['job_description'],
            'network_type' => $validated['network_type'] ?? null,
            'employment_offering' => $validated['employment_offering'] ?? null,
            'work_mode' => $validated['work_mode'],
            'employment_type' => $validated['employment_type'],
            'level' => $validated['level'] ?? null,
            'experience' => $validated['experience'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'website' => $validated['website'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'salary_type' => $validated['salary_type'] ?? null,
            'location_url' => $validated['location_url'] ?? null,
            'announcement_start_date' => $startDate,
            'announcement_end_date' => $endDate,
            'tags' => $tags,
            'information_confirmed' => ! $isDraft,
            'status' => $isDraft ? IndustryJobPostStatus::DRAFT : IndustryJobPostStatus::PUBLISHED,
            'submitted_at' => $isDraft ? null : now(),
        ]);

        $jobPost->load(['state:id,name', 'city:id,name', 'industry', 'creator:id,first_name,last_name,username,profile_image']);

        return response()->json([
            'success' => true,
            'message' => $isDraft ? 'Job post saved as draft successfully.' : 'Job post has been published successfully.',
            'data' => (new IndustryJobPostResource($jobPost, currentUser: $user))->resolve(),
        ], 201);
    }

    /**
     * Update an existing industry job post.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        if ((int) $jobPost->created_by !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this job post.',
            ], 403);
        }

        $currentStatus = $jobPost->status instanceof \BackedEnum ? $jobPost->status->value : (string) $jobPost->status;
        if ($currentStatus === IndustryJobPostStatus::REJECTED->value) {
            return response()->json([
                'success' => false,
                'message' => 'This job post has been rejected and cannot be updated.',
            ], 422);
        }

        // Verify active Pro Industry subscription & company profile
        if ($accessError = $this->validateIndustryAccess($user)) {
            return $accessError;
        }

        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft';
        $validated = $this->validateJobPost($request, $isDraft);

        $tags = $this->formatTags($validated['tags'] ?? $jobPost->tags);

        $slug = ($jobPost->job_title !== $validated['job_title'] || empty($jobPost->slug))
            ? Str::slug($validated['job_title']).'-'.$jobPost->job_id
            : $jobPost->slug;

        $startDate = $validated['announcement_start_date'] ?? $validated['start_date'] ?? $jobPost->announcement_start_date;
        $endDate = $validated['announcement_end_date'] ?? $validated['end_date'] ?? $jobPost->announcement_end_date;

        $currentStatusEnum = $jobPost->status instanceof \BackedEnum ? $jobPost->status : IndustryJobPostStatus::tryFrom((string) $jobPost->status);

        $jobPost->update([
            'slug' => $slug,
            'job_title' => $validated['job_title'],
            'position' => $validated['position'] ?? null,
            'category' => $validated['category'] ?? null,
            'sub_category' => $validated['sub_category'] ?? null,
            'job_description' => $validated['job_description'],
            'work_mode' => $validated['work_mode'],
            'employment_type' => $validated['employment_type'],
            'level' => $validated['level'] ?? null,
            'experience' => $validated['experience'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'website' => $validated['website'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'salary_type' => $validated['salary_type'] ?? null,
            'location_url' => $validated['location_url'] ?? null,
            'employment_offering' => $validated['employment_offering'] ?? null,
            'network_type' => $validated['network_type'] ?? null,
            'tags' => $tags,
            'announcement_start_date' => $startDate,
            'announcement_end_date' => $endDate,
            'information_confirmed' => ! $isDraft,
            'status' => $isDraft ? IndustryJobPostStatus::DRAFT : IndustryJobPostStatus::PUBLISHED,
            'submitted_at' => ($currentStatusEnum === IndustryJobPostStatus::DRAFT && ! $isDraft) ? now() : $jobPost->submitted_at,
        ]);

        $jobPost->load(['state:id,name', 'city:id,name', 'industry', 'creator:id,first_name,last_name,username,profile_image']);

        return response()->json([
            'success' => true,
            'message' => 'Job post updated successfully.',
            'data' => (new IndustryJobPostResource($jobPost, currentUser: $user))->resolve(),
        ]);
    }

    /**
     * Delete an industry job post.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isCreator = (int) $jobPost->created_by === (int) $user->id;

        if (! $isCreator && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this job post.',
            ], 403);
        }

        // Prevent permanent data loss of candidate submissions: if applications exist, archive rather than hard deleting
        if ($jobPost->applications()->exists()) {
            $jobPost->update(['status' => IndustryJobPostStatus::ARCHIVE]);

            return response()->json([
                'success' => true,
                'message' => 'Job post has active applications and has been archived to preserve candidate application records.',
            ]);
        }

        $jobPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job post deleted successfully.',
        ]);
    }

    /**
     * Update job post status (creator or admin).
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);

        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isCreator = (int) $jobPost->created_by === (int) $user->id;

        if (! $isAdmin && ! $isCreator) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update the status of this job post.',
            ], 403);
        }

        $currentStatus = $jobPost->status instanceof \BackedEnum ? $jobPost->status->value : (string) $jobPost->status;
        if ($isCreator && ! $isAdmin && $currentStatus === IndustryJobPostStatus::REJECTED->value) {
            return response()->json([
                'success' => false,
                'message' => 'This job post has been rejected. Creators cannot update its status.',
            ], 403);
        }

        $allowedStatuses = $isAdmin
            ? 'draft,published,rejected,archive,expired'
            : 'draft,published,archive,expired';

        $validated = $request->validate([
            'status' => ['required', 'string', "in:{$allowedStatuses}"],
            'rejection_reason' => [
                'required_if:status,rejected',
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'rejection_reason.required_if' => 'A rejection reason is required when rejecting a job post.',
        ]);

        $newStatus = $validated['status'];

        // Enforce subscription & company profile check if a creator attempts to publish
        if (! $isAdmin && $newStatus === IndustryJobPostStatus::PUBLISHED->value) {
            if ($accessError = $this->validateIndustryAccess($user)) {
                return $accessError;
            }
        }

        $updateData = [
            'status' => $newStatus,
        ];

        if ($newStatus === IndustryJobPostStatus::REJECTED->value) {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
            $updateData['reviewed_at'] = now();
        } elseif ($newStatus === IndustryJobPostStatus::PUBLISHED->value) {
            $updateData['rejection_reason'] = null;
            $updateData['reviewed_at'] = now();

            $currentStatusEnum = $jobPost->status instanceof \BackedEnum ? $jobPost->status : IndustryJobPostStatus::tryFrom((string) $jobPost->status);
            if ($currentStatusEnum === IndustryJobPostStatus::DRAFT) {
                $updateData['submitted_at'] = now();
                $updateData['information_confirmed'] = true;
            }
        }

        $jobPost->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Job post status updated to {$newStatus} successfully.",
            'data' => [
                'id' => $jobPost->id,
                'job_id' => $jobPost->job_id,
                'status' => $jobPost->status instanceof \BackedEnum ? $jobPost->status->value : $jobPost->status,
                'rejection_reason' => $jobPost->rejection_reason,
                'reviewed_at' => optional($jobPost->reviewed_at)?->toDateTimeString(),
                'updated_at' => optional($jobPost->updated_at)?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Toggle like status on a job post.
     */
    public function toggleLike(int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);
        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        return DB::transaction(function () use ($jobPost, $user) {
            $like = $jobPost->likes()->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();
                IndustryJobPost::where('id', $jobPost->id)
                    ->where('likes_count', '>', 0)
                    ->decrement('likes_count');
                $isLiked = false;
                $message = 'Job unliked successfully.';
            } else {
                $jobPost->likes()->firstOrCreate(['user_id' => $user->id]);
                $jobPost->increment('likes_count');
                $isLiked = true;
                $message = 'Job liked successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_liked' => $isLiked,
                'likes_count' => (int) $jobPost->fresh()->likes_count,
            ]);
        });
    }

    /**
     * Toggle save status on a job post.
     */
    public function toggleSave(int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jobPost = IndustryJobPost::find($id);
        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        return DB::transaction(function () use ($jobPost, $user) {
            $save = $jobPost->saves()->where('user_id', $user->id)->first();

            if ($save) {
                $save->delete();
                $isSaved = false;
                $message = 'Job removed from saved list.';
            } else {
                $jobPost->saves()->firstOrCreate(['user_id' => $user->id]);
                $isSaved = true;
                $message = 'Job saved successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_saved' => $isSaved,
            ]);
        });
    }

    /**
     * Retrieve saved jobs for current user (ordered by save date).
     */
    public function savedJobs(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();
        if (! $currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $limit = min(max($request->integer('limit', 10), 1), 100);

        // JIT auto-sync: Automatically update past-deadline published jobs to expired in DB
        IndustryJobPost::where('status', IndustryJobPostStatus::PUBLISHED->value)
            ->whereNotNull('announcement_end_date')
            ->whereDate('announcement_end_date', '<', today())
            ->update(['status' => IndustryJobPostStatus::EXPIRED]);

        $paginated = IndustryJobPost::query()
            ->join('industry_job_post_saves', 'industry_job_posts.id', '=', 'industry_job_post_saves.industry_job_post_id')
            ->where('industry_job_post_saves.user_id', $currentUser->id)
            ->select('industry_job_posts.*')
            ->with([
                'industry',
                'state:id,name',
                'city:id,name',
                'creator:id,first_name,last_name,username,profile_image',
            ])
            ->withCount('applications')
            ->orderByDesc('industry_job_post_saves.created_at')
            ->paginate($limit);

        $jobIds = $paginated->pluck('id')->all();

        $savedJobIds = ! empty($jobIds) ? array_fill_keys($jobIds, true) : [];

        $likedJobIds = ! empty($jobIds)
            ? IndustryJobPostLike::where('user_id', $currentUser->id)
                ->whereIn('industry_job_post_id', $jobIds)
                ->pluck('industry_job_post_id')
                ->flip()
                ->all()
            : [];

        $appliedJobs = ! empty($jobIds)
            ? IndustryJobApplication::where('applicant_id', $currentUser->id)
                ->whereIn('job_id', $jobIds)
                ->pluck('status', 'job_id')
                ->all()
            : [];

        $formattedData = $paginated->getCollection()->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $appliedJobs, $currentUser) {
            return (new IndustryJobPostResource($job, $likedJobIds, $savedJobIds, $appliedJobs, $currentUser))->resolve();
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Saved jobs retrieved successfully.',
            'status' => 'success',
            'data' => $formattedData,
            'total' => $paginated->total(),
            'limit' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'total_page' => $paginated->lastPage(),
        ]);
    }

    /**
     * Validate that user has an active Pro Industry subscription and a company profile.
     */
    private function validateIndustryAccess(?User $user): ?JsonResponse
    {
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        if (! $user->hasActiveSubscription(PlanType::INDUSTRY)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Pro Industry subscription plan to create or publish a job post.',
            ], 403);
        }

        $industry = $user->industry;
        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile. Please create a company profile first.',
            ], 422);
        }

        return null;
    }

    /**
     * Validate job post payload.
     */
    private function validateJobPost(Request $request, bool $isDraft): array
    {
        $isRequired = $isDraft ? 'nullable' : 'required';

        return $request->validate([
            'status' => ['nullable', 'string', Rule::enum(IndustryJobPostStatus::class)],
            'job_title' => ['required', 'string', 'max:255'],
            'position' => [$isRequired, 'string', 'max:255'],
            'category' => [$isRequired, 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'job_description' => [$isRequired, 'string', 'max:5000'],

            'work_mode' => [$isRequired, 'string', 'in:remote,on_site,hybrid'],
            'employment_type' => [$isRequired, 'string', 'in:full_time,part_time,short_term,contract,internship'],

            'level' => ['nullable', 'string', 'max:100'],
            'experience' => ['nullable', 'string', 'max:100'],
            'network_type' => [$isRequired, 'string', 'in:psychology,neuroscience'],
            'employment_offering' => [$isRequired, 'string', 'in:state,private'],

            'state_id' => [
                Rule::requiredIf(! $isDraft && $request->input('work_mode') !== 'remote'),
                'nullable',
                'integer',
                'exists:states,id',
            ],
            'city_id' => [
                Rule::requiredIf(! $isDraft && $request->input('work_mode') !== 'remote'),
                'nullable',
                'integer',
                Rule::exists('cities', 'id')->when($request->filled('state_id'), function ($rule) use ($request) {
                    return $rule->where('state_id', $request->input('state_id'));
                }),
            ],

            'email' => [$isRequired, 'email', 'max:255'],
            'phone_number' => [$isRequired, 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],

            'salary_min' => [$isRequired, 'numeric', 'min:0'],
            'salary_max' => [
                $isRequired,
                'numeric',
                $request->filled('salary_min') ? 'gte:salary_min' : 'min:0',
            ],
            'salary_type' => [$isRequired, 'string', 'in:Monthly,Yearly,Hourly'],

            'location_url' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'announcement_start_date' => ['nullable', 'date'],
            'announcement_end_date' => ['nullable', 'date', 'after_or_equal:announcement_start_date'],
            'information_confirmed' => [$isDraft ? 'nullable' : 'accepted'],
        ]);
    }

    /**
     * Format and sanitize tags payload.
     */
    private function formatTags(mixed $tags): ?array
    {
        if (is_string($tags)) {
            return array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        if (is_array($tags)) {
            return array_values(array_filter(array_map(function ($item) {
                return is_scalar($item) ? trim((string) $item) : null;
            }, $tags)));
        }

        return null;
    }

    /**
     * Generate unique job ID prefixed by industry ID.
     */
    private function generateUniqueJobId(int $industryId): string
    {
        return IndustryJobPost::generateUniqueJobId($industryId);
    }

    /**
     * Format a job card array (delegates to IndustryJobCardResource).
     */
    private function formatJobCard(IndustryJobPost $job): array
    {
        return (new IndustryJobCardResource($job))->resolve();
    }

    /**
     * Format a full job post details array (delegates to IndustryJobPostResource).
     */
    private function formatJobPost(
        IndustryJobPost $job,
        ?array $likedJobIds = null,
        ?array $savedJobIds = null,
        ?array $appliedJobs = null,
        $currentUser = null
    ): array {
        return (new IndustryJobPostResource($job, $likedJobIds, $savedJobIds, $appliedJobs, $currentUser))->resolve();
    }
}
