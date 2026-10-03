<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostSave;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobsFeedController extends Controller
{
    /**
     * Job feed er paginated list (Date check soho)
     */
    public function jobsFeed(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();

        $tab = strtolower((string) $request->query('tab', 'all'));
        $search = trim((string) $request->query('search', ''));
        $networkType = $request->query('network_type');
        $employmentOffering = $request->query('employment_offering');
        $workMode = $request->query('work_mode');
        $employmentType = $request->query('employment_type');
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $salaryType = $request->query('salary_type');
        $stateId = $request->filled('state_id') ? $request->integer('state_id') : null;
        $cityId = $request->filled('city_id') ? $request->integer('city_id') : null;

        $limit = min($request->integer('limit', 10), 100);

        $today = Carbon::today()->toDateString();

        // JIT auto-sync: Automatically update past-deadline published jobs to expired in DB
        IndustryJobPost::where('status', IndustryJobPostStatus::PUBLISHED)
            ->whereNotNull('announcement_end_date')
            ->whereDate('announcement_end_date', '<', $today)
            ->update(['status' => IndustryJobPostStatus::EXPIRED]);

        // Base Query: Status published ebong active date check
        $query = IndustryJobPost::query()
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            // Start date check: announcement start date ajke ba tar age hote hobe
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_start_date')
                    ->orWhereDate('announcement_start_date', '<=', $today);
            })
            // End date check: end date ajke ba tar cheye boro hote hobe (expire hole dekhabe na)
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_end_date')
                    ->orWhereDate('announcement_end_date', '>=', $today);
            });

        $totalJobsCount = (clone $query)->count();

        // Tab filter
        if ($tab === 'remote') {
            $query->where('work_mode', 'remote');
        } elseif (in_array($tab, ['full_time', 'full-time'])) {
            $query->where('employment_type', 'full_time');
        } elseif (in_array($tab, ['part_time', 'part-time'])) {
            $query->where('employment_type', 'part_time');
        } elseif (in_array($tab, ['short_term', 'short-term'])) {
            $query->where('employment_type', 'short_term');
        }

        // Search filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        // Additional filters
        if (! empty($networkType)) {
            $query->where('network_type', $networkType);
        }
        if (! empty($employmentOffering)) {
            $query->where('employment_offering', $employmentOffering);
        }
        if (! empty($workMode)) {
            $query->where('work_mode', $workMode);
        }
        if (! empty($employmentType)) {
            $query->where('employment_type', $employmentType);
        }
        if (! empty($stateId)) {
            $query->where('state_id', $stateId);
        }
        if (! empty($cityId)) {
            $query->where('city_id', $cityId);
        }
        if (! empty($salaryType)) {
            $query->where('salary_type', $salaryType);
        }

        if (! empty($category)) {
            is_array($category) ? $query->whereIn('category', $category) : $query->where('category', $category);
        }
        if (! empty($subCategory)) {
            is_array($subCategory) ? $query->whereIn('sub_category', $subCategory) : $query->where('sub_category', $subCategory);
        }

        // Paginate results with lightweight relationships
        $paginated = $query->with([
            'industry:id,name,slug,logo,website',
            'state:id,name',
            'city:id,name',
        ])
            ->withCount('applications')
            ->latest('id')
            ->paginate($limit);

        $formattedData = $this->formatJobCollection($paginated->getCollection(), $currentUser);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $formattedData,
            'total_jobs' => $totalJobsCount,
            'stats' => [
                'total_jobs' => $totalJobsCount,
                'filtered_jobs' => $paginated->total(),
            ],
            'pagination' => [
                'total' => $paginated->total(),
                'limit' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_page' => $paginated->lastPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ], 200);
    }

    /**
     * Job list formatter
     */
    private function formatJobCollection($jobs, $currentUser): array
    {
        if ($jobs->isEmpty()) {
            return [];
        }

        $jobIds = $jobs->pluck('id')->all();

        $likedJobIds = ($currentUser && ! empty($jobIds))
            ? IndustryJobPostLike::where('user_id', $currentUser->id)
                ->whereIn('industry_job_post_id', $jobIds)
                ->pluck('industry_job_post_id')
                ->flip()
                ->all()
            : [];

        $savedJobIds = ($currentUser && ! empty($jobIds))
            ? IndustryJobPostSave::where('user_id', $currentUser->id)
                ->whereIn('industry_job_post_id', $jobIds)
                ->pluck('industry_job_post_id')
                ->flip()
                ->all()
            : [];

        $appliedJobs = ($currentUser && ! empty($jobIds))
            ? IndustryJobApplication::where('applicant_id', $currentUser->id)
                ->whereIn('job_id', $jobIds)
                ->pluck('status', 'job_id')
                ->all()
            : [];

        return $jobs->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $appliedJobs, $currentUser) {
            return $this->formatJobPost($job, $likedJobIds, $savedJobIds, $appliedJobs, $currentUser);
        })->values()->all();
    }

    /**
     * Lightweight job post data format
     */
    private function formatJobPost(IndustryJobPost $job, array $likedJobIds = [], array $savedJobIds = [], array $appliedJobs = [], $currentUser = null): array
    {
        $locationParts = array_filter([
            $job->city?->name,
            $job->state?->name,
        ]);
        $locationText = ! empty($locationParts) ? implode(', ', $locationParts) : null;

        return [
            'id' => $job->id,
            'job_id' => $job->job_id,
            'job_title' => $job->job_title,
            'slug' => $job->slug,
            'position' => $job->position,

            'work_mode' => $job->work_mode,
            'employment_type' => $job->employment_type,
            'location' => $locationText,
            'applications_count' => $job->applications_count ?? 0,
            'created_at_human' => $job->created_at?->diffForHumans(),

            'is_saved' => isset($savedJobIds[$job->id]),
            'is_liked' => isset($likedJobIds[$job->id]),
            'is_applied' => isset($appliedJobs[$job->id]),
            'application_status' => $appliedJobs[$job->id] ?? null,

            'network_type' => $job->network_type,
            'employment_offering' => $job->employment_offering,
            'category' => $job->category,
            'sub_category' => $job->sub_category,
            'salary_type' => $job->salary_type,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'state_id' => $job->state_id,
            'city_id' => $job->city_id,

            'company' => [
                'id' => $job->industry?->id,
                'name' => $job->industry?->name,
                'slug' => $job->industry?->slug,
                'logo' => $job->industry?->logo,
                'website' => $job->industry?->website ?? $job->website,
            ],
        ];
    }
}
