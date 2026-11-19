<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\City;
use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityAdminController extends Controller
{
    /**
     * Display a paginated listing of cities with state details, jobs count, and search/filter.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $stateId = $request->input('state_id');
        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = City::query()
            ->with('state:id,name,code,slug')
            ->withCount(['industryJobPosts', 'userJobPreferences']);

        // Filter by state
        if ($stateId && is_numeric($stateId) && (int) $stateId > 0) {
            $query->where('state_id', (int) $stateId);
        }

        // Search by city name or state name / code
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('state', function (Builder $stateQ) use ($search): void {
                        $stateQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        if ($sortBy === 'jobs_count') {
            $query->orderBy('industry_job_posts_count', $sortOrder);
        } elseif ($sortBy === 'state') {
            $query->join('states', 'cities.state_id', '=', 'states.id')
                ->orderBy('states.name', $sortOrder)
                ->select('cities.*');
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('cities.created_at', $sortOrder);
        } else {
            $query->orderBy('cities.name', $sortOrder);
        }

        $cities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Cities retrieved successfully.',
            'data' => $cities->items(),
            'meta' => [
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
            ],
            'summary' => [
                'total_cities' => City::count(),
                'total_states' => State::count(),
            ],
        ]);
    }

    /**
     * Retrieve all fixed US states for filter dropdowns and city creation.
     */
    public function states(): JsonResponse
    {
        $states = State::query()
            ->select(['id', 'name', 'code', 'slug'])
            ->withCount('cities')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'States retrieved successfully.',
            'data' => $states,
        ]);
    }

    /**
     * Store a newly created city.
     */
    public function store(StoreCityRequest $request): JsonResponse
    {
        $city = City::create($request->validated());
        $city->load('state:id,name,code,slug');
        $city->loadCount(['industryJobPosts', 'userJobPreferences']);

        return response()->json([
            'success' => true,
            'message' => "City '{$city->name}' added successfully.",
            'data' => $city,
        ], 201);
    }

    /**
     * Display the specified city details and reference metrics.
     */
    public function show(int $id): JsonResponse
    {
        $city = City::with('state:id,name,code,slug')
            ->withCount(['industryJobPosts', 'userJobPreferences'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'City details retrieved successfully.',
            'data' => $city,
        ]);
    }

    /**
     * Update the specified city.
     */
    public function update(UpdateCityRequest $request, int $id): JsonResponse
    {
        $city = City::findOrFail($id);
        $city->update($request->validated());
        $city->load('state:id,name,code,slug');
        $city->loadCount(['industryJobPosts', 'userJobPreferences']);

        return response()->json([
            'success' => true,
            'message' => "City '{$city->name}' updated successfully.",
            'data' => $city,
        ]);
    }

    /**
     * Safely delete the specified city with relational integrity checks.
     */
    public function destroy(int $id): JsonResponse
    {
        $city = City::withCount(['industryJobPosts', 'userJobPreferences'])->findOrFail($id);

        $jobsCount = (int) $city->industry_job_posts_count;
        $prefsCount = (int) $city->user_job_preferences_count;

        // In-use Protection: Prevent deletion if city is referenced anywhere
        if ($jobsCount > 0 || $prefsCount > 0) {
            $reasons = [];
            if ($jobsCount > 0) {
                $reasons[] = "{$jobsCount} active job post(s)";
            }
            if ($prefsCount > 0) {
                $reasons[] = "{$prefsCount} user candidate preference(s)";
            }
            $reasonsText = implode(' and ', $reasons);

            return response()->json([
                'success' => false,
                'message' => "Cannot delete '{$city->name}'. It is currently referenced by {$reasonsText}. To protect historical and active job records, this city cannot be removed. You can edit its name instead.",
                'data' => [
                    'jobs_count' => $jobsCount,
                    'preferences_count' => $prefsCount,
                ],
            ], 422);
        }

        $cityName = $city->name;
        $city->delete();

        return response()->json([
            'success' => true,
            'message' => "City '{$cityName}' deleted successfully.",
        ]);
    }
}
