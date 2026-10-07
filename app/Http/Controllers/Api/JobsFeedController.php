<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostSave;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

        $limit = min($request->integer('limit', 10), 100);

        $today = Carbon::today()->toDateString();

        // JIT auto-sync: Automatically update past-deadline published jobs to expired in DB
        // Cached hourly so concurrent high-traffic GET requests never produce database write-lock storms
        Cache::remember('industry_jobs_expired_sync_'.$today, 3600, function () use ($today) {
            IndustryJobPost::where('status', IndustryJobPostStatus::PUBLISHED)
                ->whereNotNull('announcement_end_date')
                ->where('announcement_end_date', '<', $today)
                ->update(['status' => IndustryJobPostStatus::EXPIRED]);

            return true;
        });

        // Base Query: Status published and active date check with lightweight column selection
        $query = IndustryJobPost::query()
            ->select([
                'id',
                'job_id',
                'industry_id',
                'created_by',
                'job_title',
                'slug',
                'position',
                'work_mode',
                'employment_type',
                'level',
                'experience',
                'state_id',
                'city_id',
                'salary_min',
                'salary_max',
                'salary_type',
                'employment_offering',
                'network_type',
                'category',
                'sub_category',
                'status',
                'announcement_start_date',
                'announcement_end_date',
                'created_at',
            ])
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            // Start date check: announcement start date <= today (using direct index comparison)
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_start_date')
                    ->orWhere('announcement_start_date', '<=', $today);
            })
            // End date check: announcement end date >= today
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', $today);
            });

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

        // Network type (supports single, array, comma-separated)
        $networkTypes = $this->normalizeArrayInput($networkType);
        if (! empty($networkTypes)) {
            $query->whereIn('network_type', $networkTypes);
        }

        // Employment offering (supports single, array, comma-separated)
        $offerings = $this->normalizeArrayInput($employmentOffering);
        if (! empty($offerings)) {
            $query->whereIn('employment_offering', $offerings);
        }

        // Work mode (supports single, array, comma-separated)
        $workModes = $this->normalizeArrayInput($workMode);
        if (! empty($workModes)) {
            $query->whereIn('work_mode', $workModes);
        }

        // Employment type (supports single, array, comma-separated: full_time, part_time, etc.)
        $employmentTypes = $this->normalizeArrayInput($employmentType);
        if (! empty($employmentTypes)) {
            $query->whereIn('employment_type', $employmentTypes);
        }

        // State filter (supports state_id, state_ids[], and state name/code string)
        $rawStateIds = $request->input('state_ids', $request->input('state_id'));
        $stateIds = array_filter(array_map('intval', $this->normalizeArrayInput($rawStateIds)));
        if (! empty($stateIds)) {
            $query->whereIn('state_id', $stateIds);
        } elseif ($request->filled('state')) {
            $stateInput = trim((string) $request->input('state'));
            $query->whereHas('state', function ($q) use ($stateInput) {
                $q->where('name', $stateInput)
                    ->orWhere('code', $stateInput)
                    ->orWhere('slug', Str::slug($stateInput));
            });
        }

        // City filter (supports city_id, city_ids[], and city name strings)
        $rawCityIds = $request->input('city_ids', $request->input('city_id'));
        $cityIds = array_filter(array_map('intval', $this->normalizeArrayInput($rawCityIds)));

        $rawCityNames = $request->input('cities', $request->input('city'));
        $cityNames = $this->normalizeArrayInput($rawCityNames);
        // Exclude generic 'all cities' placeholder if submitted from UI checkbox
        $cityNames = array_values(array_filter($cityNames, fn ($n) => ! in_array(strtolower($n), ['all cities', 'all', 'all_cities'], true)));

        if (! empty($cityIds) && ! empty($cityNames)) {
            $query->where(function ($q) use ($cityIds, $cityNames) {
                $q->whereIn('city_id', $cityIds)
                    ->orWhereHas('city', fn ($sub) => $sub->whereIn('name', $cityNames));
            });
        } elseif (! empty($cityIds)) {
            $query->whereIn('city_id', $cityIds);
        } elseif (! empty($cityNames)) {
            $query->whereHas('city', fn ($q) => $q->whereIn('name', $cityNames));
        }

        // Salary type
        if (! empty($salaryType)) {
            $query->where('salary_type', $salaryType);
        }

        // Salary range filters (minimum and maximum)
        if ($request->filled('salary_min')) {
            $salaryMin = (float) $request->query('salary_min');
            $query->where(function ($q) use ($salaryMin) {
                $q->where('salary_max', '>=', $salaryMin)
                    ->orWhere(function ($sub) use ($salaryMin) {
                        $sub->whereNull('salary_max')->where('salary_min', '>=', $salaryMin);
                    });
            });
        }

        if ($request->filled('salary_max')) {
            $salaryMax = (float) $request->query('salary_max');
            $query->where(function ($q) use ($salaryMax) {
                $q->where('salary_min', '<=', $salaryMax)
                    ->orWhere(function ($sub) use ($salaryMax) {
                        $sub->whereNull('salary_min')->where('salary_max', '<=', $salaryMax);
                    });
            });
        }

        // Position filter
        $positions = $this->normalizeArrayInput($request->query('position'));
        if (! empty($positions)) {
            $query->whereIn('position', $positions);
        }

        // Level / Seniority filter
        $levels = $this->normalizeArrayInput($request->query('level'));
        if (! empty($levels)) {
            $query->whereIn('level', $levels);
        }

        // Experience filter
        $experiences = $this->normalizeArrayInput($request->query('experience'));
        if (! empty($experiences)) {
            $query->whereIn('experience', $experiences);
        }

        // Industry / Company filter
        $rawIndustryIds = $request->input('industry_ids', $request->input('industry_id'));
        $industryIds = array_filter(array_map('intval', $this->normalizeArrayInput($rawIndustryIds)));
        if (! empty($industryIds)) {
            $query->whereIn('industry_id', $industryIds);
        }

        // Category & Sub-category (supports single, array, comma-separated)
        $categories = $this->normalizeArrayInput($category);
        if (! empty($categories)) {
            $query->whereIn('category', $categories);
        }

        $subCategories = $this->normalizeArrayInput($subCategory);
        if (! empty($subCategories)) {
            $query->whereIn('sub_category', $subCategories);
        }

        // Filtered total count for accurate statistics
        $totalJobsCount = (clone $query)->count();

        // Paginate results with lightweight relationships
        $paginated = $query->with([
            'industry:id,name,slug,logo,website',
            'state:id,name',
            'city:id,name',
        ])
            ->withCount('applications')
            ->latest('id')
            ->cursorPaginate($limit);

        $formattedData = $this->formatJobCollection($paginated->getCollection(), $currentUser);

        $responsePayload = [
            'success' => true,
            'status' => 'success',
            'data' => $formattedData,
            'total_jobs' => $totalJobsCount,
            'stats' => [
                'total_jobs' => $totalJobsCount,
            ],
            'pagination' => [
                'limit' => $paginated->perPage(),
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ];

        // Optional city grouping if requested by client (e.g., ?group_by=city)
        if ($request->query('group_by') === 'city' || $request->boolean('grouped')) {
            $responsePayload['grouped_by_city'] = $this->groupJobsByCity(
                $paginated->getCollection(),
                $formattedData,
                $cityIds,
                $cityNames,
                $stateIds
            );
        }

        return response()->json($responsePayload, 200);
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
            'level' => $job->level,
            'experience' => $job->experience,
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

    /**
     * Helper to normalize input into an array of trimmed strings.
     */
    private function normalizeArrayInput(mixed $input): array
    {
        if (empty($input)) {
            return [];
        }

        if (is_array($input)) {
            $result = [];
            foreach ($input as $item) {
                if (is_string($item) && str_contains($item, ',')) {
                    $result = array_merge($result, explode(',', $item));
                } elseif (is_scalar($item)) {
                    $result[] = (string) $item;
                }
            }

            return array_values(array_filter(array_map('trim', $result), fn ($val) => $val !== ''));
        }

        if (is_string($input)) {
            return array_values(array_filter(array_map('trim', explode(',', $input)), fn ($val) => $val !== ''));
        }

        return [(string) $input];
    }

    /**
     * Group formatted jobs by city, including selected cities that have 0 jobs.
     */
    private function groupJobsByCity(
        iterable $jobsCollection,
        array $formattedJobs,
        array $selectedCityIds = [],
        array $selectedCityNames = [],
        array $selectedStateIds = []
    ): array {
        $formattedById = [];
        foreach ($formattedJobs as $job) {
            $formattedById[$job['id']] = $job;
        }

        $grouped = [];

        // 1. If specific city IDs were requested, initialize them in the exact requested order
        if (! empty($selectedCityIds)) {
            $requestedCities = City::whereIn('id', $selectedCityIds)->pluck('name', 'id')->all();
            foreach ($selectedCityIds as $id) {
                if (isset($requestedCities[$id])) {
                    $name = $requestedCities[$id];
                    $grouped[$id] = [
                        'city_id' => (int) $id,
                        'city_name' => $name,
                        'title' => "{$name} Jobs",
                        'total' => 0,
                        'jobs' => [],
                    ];
                }
            }
        } elseif (empty($selectedCityNames) && ! empty($selectedStateIds)) {
            // When "All Cities" is selected for a state, include all cities of that state
            $stateCities = City::whereIn('state_id', $selectedStateIds)->orderBy('name')->pluck('name', 'id')->all();
            foreach ($stateCities as $id => $name) {
                $grouped[$id] = [
                    'city_id' => (int) $id,
                    'city_name' => $name,
                    'title' => "{$name} Jobs",
                    'total' => 0,
                    'jobs' => [],
                ];
            }
        }

        // 2. If specific city names were requested, initialize them too
        if (! empty($selectedCityNames)) {
            foreach ($selectedCityNames as $name) {
                $key = 'name_'.strtolower($name);
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'city_id' => null,
                        'city_name' => $name,
                        'title' => "{$name} Jobs",
                        'total' => 0,
                        'jobs' => [],
                    ];
                }
            }
        }

        // 3. Populate jobs into their corresponding city groups
        foreach ($jobsCollection as $job) {
            $cId = $job->city_id ?? 0;
            $cName = $job->city?->name ?? 'Other';
            $targetKey = isset($grouped[$cId])
                ? $cId
                : (isset($grouped['name_'.strtolower($cName)]) ? 'name_'.strtolower($cName) : $cId);

            if (! isset($grouped[$targetKey])) {
                $grouped[$targetKey] = [
                    'city_id' => $job->city_id,
                    'city_name' => $cName,
                    'title' => $cName !== 'Other' ? "{$cName} Jobs" : 'Other Jobs',
                    'total' => 0,
                    'jobs' => [],
                ];
            }

            if (isset($formattedById[$job->id])) {
                $grouped[$targetKey]['jobs'][] = $formattedById[$job->id];
                $grouped[$targetKey]['total']++;
            }
        }

        return array_values($grouped);
    }
}
