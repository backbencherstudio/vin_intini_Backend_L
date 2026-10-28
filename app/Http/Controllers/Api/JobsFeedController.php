<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostSave;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        } elseif ($tab === 'hybrid') {
            $query->where('work_mode', 'hybrid');
        } elseif (in_array($tab, ['on_site', 'on-site'])) {
            $query->where('work_mode', 'on_site');
        } elseif (in_array($tab, ['full_time', 'full-time'])) {
            $query->where('employment_type', 'full_time');
        } elseif (in_array($tab, ['part_time', 'part-time'])) {
            $query->where('employment_type', 'part_time');
        } elseif (in_array($tab, ['short_term', 'short-term'])) {
            $query->where('employment_type', 'short_term');
        } elseif ($tab === 'contract') {
            $query->where('employment_type', 'contract');
        } elseif ($tab === 'internship') {
            $query->where('employment_type', 'internship');
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

        // State filter (supports state_id, state_ids[], code, codes, and state name strings)
        $stateFilter = $this->resolveStateFilter($request);
        $stateIds = $stateFilter['state_ids'];
        if ($stateFilter['has_filter']) {
            if (! empty($stateIds)) {
                $query->whereIn('state_id', $stateIds);
            } else {
                $query->whereRaw('1 = 0');
            }
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
            'industry:id,name,slug,logo,website,created_by',
            'state:id,name,code,slug',
            'city:id,name,state_id',
        ])
            ->withCount('applications')
            ->latest('id')
            ->cursorPaginate($limit);

        $formattedData = $this->formatJobCollection($paginated->getCollection(), $currentUser);

        // Resolve searched state information for the response
        $stateData = null;
        if (! empty($stateIds)) {
            $statesQuery = State::query()->select(['id', 'name', 'code', 'slug'])->whereIn('id', $stateIds)->get();
            if (count($stateIds) === 1) {
                $first = $statesQuery->first();
                $stateData = $first ? [
                    'id' => (int) $first->id,
                    'name' => $first->name,
                    'code' => $first->code,
                    'slug' => $first->slug,
                ] : null;
            } else {
                $stateData = $statesQuery->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'slug' => $s->slug,
                ])->values()->all();
            }
        }

        // Resolve searched cities information for the response
        $citiesData = [];
        $searchedCities = collect();
        if (! empty($cityIds) || ! empty($cityNames)) {
            $citiesQuery = City::query()->select(['id', 'state_id', 'name']);
            if (! empty($cityIds) && ! empty($cityNames)) {
                $citiesQuery->where(function ($q) use ($cityIds, $cityNames) {
                    $q->whereIn('id', $cityIds)
                        ->orWhereIn('name', $cityNames);
                });
            } elseif (! empty($cityIds)) {
                $citiesQuery->whereIn('id', $cityIds);
            } else {
                $citiesQuery->whereIn('name', $cityNames);
            }
            $searchedCities = $citiesQuery->get();

            $citiesData = $searchedCities->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
            ])->values()->all();
        }

        // Auto-resolve state if state was not explicitly specified but all searched cities belong to a single state
        if ($stateData === null && $searchedCities->isNotEmpty()) {
            $uniqueStateIds = $searchedCities->pluck('state_id')->filter()->unique()->values()->all();
            if (count($uniqueStateIds) === 1) {
                $autoState = State::query()->select(['id', 'name', 'code', 'slug'])->find($uniqueStateIds[0]);
                if ($autoState) {
                    $stateData = [
                        'id' => (int) $autoState->id,
                        'name' => $autoState->name,
                        'code' => $autoState->code,
                        'slug' => $autoState->slug,
                    ];
                }
            }
        }

        $responsePayload = [
            'success' => true,
            'status' => 'success',
            'state' => $stateData,
            'cities' => $citiesData,
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
                $stateIds,
                (clone $query),
                $request->integer('per_city_limit', 0)
            );
        }

        return response()->json($responsePayload, 200);
    }

    /**
     * Get active jobs filtered by state_id and employment_offering
     */
    public function jobsByStateAndOffering(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();
        $today = Carbon::today()->toDateString();

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
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_start_date')
                    ->orWhere('announcement_start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', $today);
            });

        // Filter by state_id or code (supports single ID/code, array, or comma-separated)
        $stateFilter = $this->resolveStateFilter($request);
        $stateIds = $stateFilter['state_ids'];
        $requestedCodes = $stateFilter['requested_codes'];

        if ($stateFilter['has_filter']) {
            if (! empty($stateIds)) {
                $query->whereIn('state_id', $stateIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Optional network_type filter
        if ($request->filled('network_type')) {
            $networkTypes = $this->normalizeArrayInput($request->input('network_type'));
            if (! empty($networkTypes)) {
                $query->whereIn('network_type', $networkTypes);
            }
        }

        // Search filter by job_title, job_id, position, or numeric id
        $search = trim((string) $request->input('search', $request->input('query', $request->input('q', ''))));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        // Aggregate counts by employment offering before applying employment_offering filter
        $statsQuery = clone $query;
        $statsQuery->getQuery()->columns = null;
        $offeringStats = $statsQuery
            ->selectRaw("
                COUNT(CASE WHEN LOWER(employment_offering) = 'private' THEN 1 END) as private_jobs_count,
                COUNT(CASE WHEN LOWER(employment_offering) IN ('state', 'public') THEN 1 END) as state_jobs_count
            ")
            ->first();

        $privateJobsCount = (int) ($offeringStats->private_jobs_count ?? 0);
        $stateJobsCount = (int) ($offeringStats->state_jobs_count ?? 0);

        // Filter by employment_offering (supports single, array, or comma-separated)
        $rawOfferings = $request->input('employment_offering', $request->input('employment_offerings'));
        $offerings = $this->normalizeArrayInput($rawOfferings);
        if (! empty($offerings)) {
            $effectiveOfferings = $offerings;
            if (in_array('state', $offerings, true) && ! in_array('public', $offerings, true)) {
                $effectiveOfferings[] = 'public';
            }
            $query->whereIn('employment_offering', $effectiveOfferings);
        }

        $limit = min($request->integer('limit', $request->integer('per_page', 10)), 100);

        // Filtered total count for accurate statistics
        $totalJobsCount = (clone $query)->count();

        $paginated = $query->with([
            'industry:id,name,slug,logo,website,created_by',
            'state:id,name,code,slug',
            'city:id,name,state_id',
        ])
            ->withCount('applications')
            ->latest('id')
            ->cursorPaginate($limit);

        $formattedData = $this->formatJobCollection($paginated->getCollection(), $currentUser);

        $stateData = null;
        if (! empty($stateIds)) {
            $statesQuery = State::query()->select(['id', 'name', 'code', 'slug'])->whereIn('id', $stateIds)->get();
            if (count($stateIds) === 1) {
                $first = $statesQuery->first();
                $stateData = $first ? [
                    'id' => $first->id,
                    'name' => $first->name,
                    'code' => $first->code,
                    'slug' => $first->slug,
                ] : null;
            } else {
                $stateData = $statesQuery->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'slug' => $s->slug,
                ])->values()->all();
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Jobs filtered by state and employment offering retrieved successfully.',
            'state' => $stateData,
            'data' => $formattedData,
            'total_jobs' => $totalJobsCount,
            'private_jobs_count' => $privateJobsCount,
            'state_jobs_count' => $stateJobsCount,
            'pagination' => [
                'limit' => $paginated->perPage(),
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'state_id' => ! empty($stateIds) ? (count($stateIds) === 1 ? reset($stateIds) : array_values($stateIds)) : null,
                'code' => ! empty($requestedCodes)
                    ? (count($requestedCodes) === 1 ? reset($requestedCodes) : array_values($requestedCodes))
                    : (is_array($stateData) && isset($stateData['code']) ? $stateData['code'] : null),
                'employment_offering' => ! empty($offerings) ? (count($offerings) === 1 ? reset($offerings) : array_values($offerings)) : null,
            ],
        ], 200);
    }

    /**
     * Get active jobs count grouped by state with optional network_type and related filters.
     */
    public function jobsCountByState(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        // JIT auto-sync: Automatically update past-deadline published jobs to expired
        Cache::remember('industry_jobs_expired_sync_'.$today, 3600, function () use ($today) {
            IndustryJobPost::where('status', IndustryJobPostStatus::PUBLISHED)
                ->whereNotNull('announcement_end_date')
                ->where('announcement_end_date', '<', $today)
                ->update(['status' => IndustryJobPostStatus::EXPIRED]);

            return true;
        });

        // Base query: Published active jobs with assigned state
        $query = IndustryJobPost::query()
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_start_date')
                    ->orWhere('announcement_start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', $today);
            })
            ->whereNotNull('state_id');

        // Filter by network_type (supports single string, comma-separated, or array)
        $rawNetworkType = $request->input('network_type');
        $networkTypes = $this->normalizeArrayInput($rawNetworkType);
        if (! empty($networkTypes)) {
            $query->whereIn('network_type', $networkTypes);
        }

        // Optional filter by employment_offering
        $rawOfferings = $request->input('employment_offering', $request->input('employment_offerings'));
        $offerings = $this->normalizeArrayInput($rawOfferings);
        if (! empty($offerings)) {
            $query->whereIn('employment_offering', $offerings);
        }

        // Optional filter by work_mode
        $rawWorkModes = $request->input('work_mode');
        $workModes = $this->normalizeArrayInput($rawWorkModes);
        if (! empty($workModes)) {
            $query->whereIn('work_mode', $workModes);
        }

        // Optional filter by category
        $rawCategories = $request->input('category');
        $categories = $this->normalizeArrayInput($rawCategories);
        if (! empty($categories)) {
            $query->whereIn('category', $categories);
        }

        // Optional filter by state_id / state_ids or code / codes
        $stateFilter = $this->resolveStateFilter($request);
        if ($stateFilter['has_filter']) {
            if (! empty($stateFilter['state_ids'])) {
                $query->whereIn('state_id', $stateFilter['state_ids']);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Aggregate counts grouped by state_id and network_type
        $aggregatedRows = (clone $query)
            ->selectRaw('state_id, network_type, COUNT(*) as count')
            ->groupBy('state_id', 'network_type')
            ->get();

        $stateActiveCounts = [];
        $stateBreakdowns = [];
        $networkTypeTotals = [];
        $totalJobs = 0;

        foreach ($aggregatedRows as $row) {
            $sId = (int) $row->state_id;
            $nType = (string) ($row->network_type ?? 'unspecified');
            $c = (int) $row->count;

            $stateActiveCounts[$sId] = ($stateActiveCounts[$sId] ?? 0) + $c;
            $stateBreakdowns[$sId][$nType] = ($stateBreakdowns[$sId][$nType] ?? 0) + $c;
            $networkTypeTotals[$nType] = ($networkTypeTotals[$nType] ?? 0) + $c;
            $totalJobs += $c;
        }

        // Fetch states (all states by default, or filtered if specific state_ids provided)
        $statesQuery = State::query()->select(['id', 'name', 'code', 'slug']);
        if (! empty($stateIds)) {
            $statesQuery->whereIn('id', $stateIds);
        }
        $states = $statesQuery->orderBy('name')->get();

        $onlyWithJobs = $request->boolean('only_with_jobs') || $request->boolean('only_active');
        $result = [];

        foreach ($states as $state) {
            $count = (int) ($stateActiveCounts[$state->id] ?? 0);

            if (! $onlyWithJobs || $count > 0) {
                $result[] = [
                    'state_id' => $state->id,
                    'state_name' => $state->name,
                    'state_code' => $state->code,
                    'state_slug' => $state->slug,
                    'active_jobs_count' => $count,
                    'network_types_breakdown' => $stateBreakdowns[$state->id] ?? [],
                ];
            }
        }

        // Optional sorting: if sort=count or order_by=count, sort by active_jobs_count descending
        $sortBy = strtolower((string) $request->input('sort', $request->input('order_by', 'name')));
        if (in_array($sortBy, ['count', 'jobs_count', 'active_jobs_count'], true)) {
            usort($result, fn ($a, $b) => $b['active_jobs_count'] <=> $a['active_jobs_count']);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Active jobs count by state retrieved successfully.',
            'total_active_jobs' => $totalJobs,
            'total_states' => count($result),
            'summary' => [
                'total_active_jobs' => $totalJobs,
                'total_states_with_jobs' => count($stateActiveCounts),
                'total_states_count' => $states->count(),
                'network_types' => $networkTypeTotals,
            ],
            'filters' => [
                'network_type' => ! empty($networkTypes) ? (count($networkTypes) === 1 ? reset($networkTypes) : array_values($networkTypes)) : null,
                'employment_offering' => ! empty($offerings) ? (count($offerings) === 1 ? reset($offerings) : array_values($offerings)) : null,
                'work_mode' => ! empty($workModes) ? (count($workModes) === 1 ? reset($workModes) : array_values($workModes)) : null,
                'category' => ! empty($categories) ? (count($categories) === 1 ? reset($categories) : array_values($categories)) : null,
                'state_id' => ! empty($stateIds) ? (count($stateIds) === 1 ? reset($stateIds) : array_values($stateIds)) : null,
                'only_with_jobs' => $onlyWithJobs,
            ],
            'data' => $result,
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

        $userIndustryIds = ($currentUser)
            ? Industry::where('created_by', $currentUser->id)->pluck('id')->flip()->all()
            : [];

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

        return $jobs->map(function (IndustryJobPost $job) use ($likedJobIds, $savedJobIds, $appliedJobs, $userIndustryIds, $currentUser) {
            return $this->formatJobPost($job, $likedJobIds, $savedJobIds, $appliedJobs, $userIndustryIds, $currentUser);
        })->values()->all();
    }

    /**
     * Lightweight job post data format
     */
    private function formatJobPost(
        IndustryJobPost $job,
        array $likedJobIds = [],
        array $savedJobIds = [],
        array $appliedJobs = [],
        array $userIndustryIds = [],
        $currentUser = null
    ): array {
        $locationParts = array_filter([
            $job->city?->name,
            $job->state?->name,
        ]);
        $locationText = ! empty($locationParts) ? implode(', ', $locationParts) : null;

        $status = $job->status instanceof \BackedEnum
            ? $job->status->value
            : ($job->status !== null ? (string) $job->status : null);

        if ($status === IndustryJobPostStatus::PUBLISHED->value && $job->isExpired()) {
            $status = IndustryJobPostStatus::EXPIRED->value;
        }

        $isOwnIndustry = (bool) (
            $currentUser && (
                ((int) $job->created_by === (int) $currentUser->id) ||
                ($job->industry_id && isset($userIndustryIds[$job->industry_id])) ||
                ($job->industry && (int) $job->industry->created_by === (int) $currentUser->id)
            )
        );

        return [
            'id' => $job->id,
            'job_id' => $job->job_id,
            'job_title' => $job->job_title,
            'slug' => $job->slug,
            'position' => $job->position,
            'status' => $status,

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
            'state_name' => $job->state?->name,
            'city_id' => $job->city_id,
            'city_name' => $job->city?->name,

            'industry' => [
                'id' => $job->industry?->id,
                'name' => $job->industry?->name,
                'slug' => $job->industry?->slug,
                'logo' => $job->industry?->logo,
                'website' => $job->industry?->website ?? $job->website,
                'is_own_industry' => $isOwnIndustry,
            ],
        ];
    }

    /**
     * Resolve state IDs and codes from request inputs (state_id, state_ids, code, codes, state_code, state_codes, state).
     *
     * @return array{state_ids: int[], has_filter: bool, requested_codes: string[]}
     */
    private function resolveStateFilter(Request $request): array
    {
        $rawStateIdInputs = $this->normalizeArrayInput(
            $request->input('state_ids', $request->input('state_id'))
        );

        $rawStateCodeInputs = $this->normalizeArrayInput(
            $request->input('state_codes', $request->input('state_code', $request->input('codes', $request->input('code', $request->input('state')))))
        );

        $rawCombined = array_merge($rawStateIdInputs, $rawStateCodeInputs);

        if (empty($rawCombined)) {
            return [
                'state_ids' => [],
                'has_filter' => false,
                'requested_codes' => [],
            ];
        }

        $numericIds = [];
        $stringCodes = [];

        foreach ($rawCombined as $item) {
            if (is_numeric($item)) {
                $numericIds[] = (int) $item;
            } else {
                $trimmed = trim((string) $item);
                if ($trimmed !== '') {
                    $stringCodes[] = $trimmed;
                }
            }
        }

        $resolvedIds = $numericIds;

        if (! empty($stringCodes)) {
            $upperCodes = array_map('strtoupper', $stringCodes);
            $lowerSlugs = array_map('strtolower', $stringCodes);

            $matchedIds = State::query()
                ->where(function ($q) use ($upperCodes, $lowerSlugs, $stringCodes) {
                    $q->whereIn('code', $upperCodes)
                        ->orWhereIn('slug', $lowerSlugs)
                        ->orWhereIn('name', $stringCodes);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $resolvedIds = array_merge($resolvedIds, $matchedIds);
        }

        return [
            'state_ids' => array_values(array_unique(array_filter($resolvedIds))),
            'has_filter' => true,
            'requested_codes' => array_values(array_unique(array_map('strtoupper', $stringCodes))),
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
        array $selectedStateIds = [],
        $baseQuery = null,
        int $perCityLimit = 0
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
                        'has_more' => false,
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
                    'has_more' => false,
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
                        'has_more' => false,
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
                    'has_more' => false,
                    'jobs' => [],
                ];
            }

            if (isset($formattedById[$job->id])) {
                $grouped[$targetKey]['jobs'][] = $formattedById[$job->id];
            }
        }

        // 4. Calculate real database totals per city and has_more indicator for Show All support
        $cityTotals = [];
        if ($baseQuery !== null) {
            try {
                $cityTotals = (clone $baseQuery)
                    ->reorder()
                    ->groupBy('city_id')
                    ->selectRaw('city_id, count(*) as aggregate_total')
                    ->pluck('aggregate_total', 'city_id')
                    ->all();
            } catch (\Throwable) {
                $cityTotals = [];
            }
        }

        foreach ($grouped as &$group) {
            $cId = $group['city_id'];
            if ($cId !== null && isset($cityTotals[$cId])) {
                $group['total'] = (int) $cityTotals[$cId];
            } else {
                $group['total'] = count($group['jobs']);
            }

            if ($perCityLimit > 0 && count($group['jobs']) > $perCityLimit) {
                $group['jobs'] = array_slice($group['jobs'], 0, $perCityLimit);
            }

            $group['has_more'] = $group['total'] > count($group['jobs']);
        }
        unset($group);

        return array_values($grouped);
    }
}
