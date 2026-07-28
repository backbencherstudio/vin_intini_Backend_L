<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StateResource;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademiaController extends Controller
{
    public function getStates(): JsonResponse
    {
        $states = State::withCount([
            'universities',
            'residencies',
            'facilities',
            'jobs',
        ])->get();

        return response()->json([
            'success' => true,
            'data' => StateResource::collection($states),
        ]);
    }

    public function getStateDetails($code): JsonResponse
    {
        $state = State::where('code', $code)
            ->with(['universities', 'residencies', 'facilities', 'jobs'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new StateResource($state),
        ]);
    }

    public function getUniversities(Request $request, $code): JsonResponse
    {
        $degreeFilter = $request->query('degree', 'All');
        $search = trim((string) $request->query('search', ''));

        $sortOrder = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

        $perPage = $request->integer('limit', $request->integer('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $state = State::where('code', $code)->first();

        if (! $state) {
            return response()->json(['status' => 'error', 'message' => 'State not found'], 404);
        }

        $query = $state->universities();

        if ($search !== '') {
            $query->where('academia_universities.name', 'like', '%'.$search.'%');
        }

        if ($degreeFilter !== 'All' && ! empty($degreeFilter)) {
            $query->where(function ($q) use ($degreeFilter) {
                $q->whereJsonContains('psychology_degrees', $degreeFilter)
                    ->orWhereJsonContains('counseling_degrees', $degreeFilter)
                    ->orWhereJsonContains('neuroscience_degrees', $degreeFilter);
            });
        }

        // if ($degreeFilter !== 'All' && !empty($degreeFilter)) {
        //     $query->whereJsonContains('psychology_degrees', $degreeFilter);
        // }

        // alphabetical order
        $query->orderBy('academia_universities.name', $sortOrder);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($university) use ($state) {
            $university->state_name = $state->name;
            $university->state_code = $state->code;

            return $university;
        });

        return response()->json([
            'success' => true,
            'message' => 'Universities retrieved successfully.',
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total_page' => $paginator->lastPage(),
            'filters' => [
                'applied_degree' => $degreeFilter,
                'search' => $search ?: null,
                'sort' => $sortOrder,
            ],
        ], 200);
    }

    public function getResidencies(Request $request, $code): JsonResponse
    {
        $degreeFilter = $request->query('degree', 'All');
        $search = trim((string) $request->query('search', ''));
        $perPage = $request->integer('limit', $request->integer('per_page', 15));
        $perPage = max(1, min($perPage, 100));
        $sortOrder = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

        $state = State::where('code', $code)->first();

        if (! $state) {
            return response()->json([
                'status' => 'error',
                'message' => 'State not found',
            ], 404);
        }

        $query = $state->residencies();

        if ($search !== '') {
            $query->where('academia_medical_residencies.program_name', 'like', '%'.$search.'%');
        }

        if ($degreeFilter !== 'All' && ! empty($degreeFilter)) {
            $query->whereJsonContains('degree_types', $degreeFilter);
        }

        // alphabetical order
        $query->orderBy('academia_medical_residencies.program_name', $sortOrder);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($residency) use ($state) {
            $residency->state_name = $state->name;
            $residency->state_code = $state->code;

            return $residency;
        });

        return response()->json([
            'success' => true,
            'message' => 'Residencies retrieved successfully.',
            'status' => 'success',
            // 'state_name' => $state->name,
            'data' => $paginator->items(),
            'stats' => [
                'total_residencies' => $paginator->total(),
            ],
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'applied_degree' => $degreeFilter,
                'search' => $search !== '' ? $search : null,
                'sort' => $sortOrder,
            ],
        ], 200);
    }

    public function getFacilities(Request $request, $code): JsonResponse
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));
        $perPage = $request->integer('limit', $request->integer('per_page', 15));
        $perPage = max(1, min($perPage, 100));
        $sortOrder = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

        $state = State::where('code', $code)->first();

        if (! $state) {
            return response()->json([
                'status' => 'error',
                'message' => 'State not found',
            ], 404);
        }

        $query = $state->facilities();

        $query->when($type, function ($q) use ($type) {
            return $q->where('type', $type);
        });

        $query->when($search !== '', function ($q) use ($search) {
            return $q->where('academia_facilities.name', 'like', '%'.$search.'%');
        });

        // alphabetical order
        $query->orderBy('academia_facilities.name', $sortOrder);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($facility) use ($state) {
            $facility->state_name = $state->name;
            $facility->state_code = $state->code;

            return $facility;
        });

        return response()->json([
            'success' => true,
            'message' => 'Facilities retrieved successfully.',
            'status' => 'success',
            // 'state_name' => $state->name,
            'data' => $paginator->items(),
            'stats' => [
                'total_facilities' => $paginator->total(),
            ],
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'type' => $type ?: null,
                'search' => $search !== '' ? $search : null,
                'sort' => $sortOrder,
            ],
        ], 200);
    }

    // public function getJobs(Request $request, $code): JsonResponse
    // {
    //     $category = $request->query('category'); // state_institution, private_practice
    //     $search = trim((string) $request->query('search', ''));
    //     $perPage = $request->integer('limit', $request->integer('per_page', 15));
    //     $perPage = max(1, min($perPage, 100));
    //     $sortOrder = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

    //     $state = State::where('code', $code)->first();

    //     if (!$state) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'State not found',
    //         ], 404);
    //     }

    //     $query = $state->jobs();

    //     $query->when($category, function ($q) use ($category) {
    //         return $q->where('category', $category);
    //     });

    //     $query->when($search !== '', function ($q) use ($search) {
    //         return $q->where(function ($sub) use ($search) {
    //             $sub->where('title', 'like', '%' . $search . '%')
    //                 ->orWhere('company_name', 'like', '%' . $search . '%');
    //         });
    //     });

    //     $query->orderBy('title', $sortOrder);

    //     $paginator = $query->paginate($perPage);

    //     $paginator->getCollection()->transform(function ($job) use ($state) {
    //         $job->state_name = $state->name;
    //         $job->state_code = $state->code;
    //         return $job;
    //     });

    //     $stateInstitutionCount = $state->jobs()->where('category', 'state_institution')->count();
    //     $privatePracticeCount = $state->jobs()->where('category', 'private_practice')->count();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Jobs retrieved successfully.',
    //         'status' => 'success',
    //         'data' => $paginator->items(),
    //         'stats' => [
    //             'total_jobs' => $paginator->total(),
    //             'state_institution_offerings' => $stateInstitutionCount,
    //             'private_practice_offerings' => $privatePracticeCount,
    //         ],
    //         'total' => $paginator->total(),
    //         'limit' => $paginator->perPage(),
    //         'current_page' => $paginator->currentPage(),
    //         'total_page' => $paginator->lastPage(),
    //         'last_page' => $paginator->lastPage(),
    //         'filters' => [
    //             'category' => $category ?: null,
    //             'search' => $search !== '' ? $search : null,
    //             'sort' => $sortOrder,
    //         ],
    //     ], 200);
    // }

    public function getJobs(Request $request, $code): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = $request->integer('limit', $request->integer('per_page', 15));
        $perPage = max(1, min($perPage, 100));
        $sortOrder = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

        $state = State::where('code', $code)->first();

        if (! $state) {
            return response()->json([
                'status' => 'error',
                'message' => 'State not found',
            ], 404);
        }

        $baseQuery = $state->jobs();
        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%");
            });
        }

        $stateTotalCount = (clone $baseQuery)->where('category', 'state_institution')->count();
        $privateTotalCount = (clone $baseQuery)->where('category', 'private_practice')->count();

        $statePaginator = (clone $baseQuery)->where('category', 'state_institution')
            ->orderBy('title', $sortOrder)
            ->paginate($perPage, ['*'], 'page');

        $privatePaginator = (clone $baseQuery)->where('category', 'private_practice')
            ->orderBy('title', $sortOrder)
            ->paginate($perPage, ['*'], 'page');

        $lastPage = max($statePaginator->lastPage(), $privatePaginator->lastPage());

        return response()->json([
            'success' => true,
            'message' => 'Jobs retrieved successfully.',
            'status' => 'success',
            'data' => [
                'state_institution' => [
                    'total_offerings' => $stateTotalCount,
                    'items' => $statePaginator->items(),
                ],
                'private_practice' => [
                    'total_offerings' => $privateTotalCount,
                    'items' => $privatePaginator->items(),
                ],
            ],
            'total' => $stateTotalCount + $privateTotalCount,
            'limit' => $perPage,
            'current_page' => $statePaginator->currentPage(),
            'total_page' => $lastPage,
            'last_page' => $lastPage,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'sort' => $sortOrder,
            ],
        ], 200);
    }
}
