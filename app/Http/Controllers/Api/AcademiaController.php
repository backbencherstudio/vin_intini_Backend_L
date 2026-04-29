<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Http\Resources\StateResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AcademiaController extends Controller
{
    public function getStates(): JsonResponse
    {
        $states = State::withCount([
            'universities',
            'residencies',
            'facilities',
            'jobs'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => StateResource::collection($states)
        ]);
    }

    public function getStateDetails($code): JsonResponse
    {
        $state = State::where('code', $code)
            ->with(['universities', 'residencies', 'facilities', 'jobs'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new StateResource($state)
        ]);
    }

    // public function getUniversities(Request $request, $code): JsonResponse
    // {
    //     $search = trim((string) $request->query('search', ''));
    //     $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

    //     $state = State::where('code', $code)->first();

    //     if (!$state) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'State not found',
    //         ], 404);
    //     }

    //     $query = $state->universities();

    //     if ($search !== '') {
    //         $query->where('name', 'like', '%' . $search . '%');
    //     }

    //     $paginator = $query->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Universities retrieved successfully.',
    //         'status' => 'success',
    //         'state_name' => $state->name,
    //         'data' => $paginator->items(),
    //         'stats' => [
    //             'total_universities' => $paginator->total(),
    //         ],
    //         'total' => $paginator->total(),
    //         'limit' => $paginator->perPage(),
    //         'current_page' => $paginator->currentPage(),
    //         'total_page' => $paginator->lastPage(),
    //         'last_page' => $paginator->lastPage(),
    //         'filters' => [
    //             'search' => $search !== '' ? $search : null,
    //         ],
    //     ], 200);
    // }

    public function getUniversities(Request $request, $code): JsonResponse
    {
        $degreeFilter = $request->query('degree', 'All');
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $state = State::where('code', $code)->first();

        if (!$state) {
            return response()->json(['status' => 'error', 'message' => 'State not found'], 404);
        }

        $query = $state->universities();

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($degreeFilter !== 'All' && !empty($degreeFilter)) {
            $query->whereJsonContains('psychology_degrees', $degreeFilter);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Universities retrieved successfully.',
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'applied_degree' => $degreeFilter, 
                'search' => $search ?: null,
            ],
        ], 200);
    }

    // public function getUniversities($code): JsonResponse
    // {
    //     $state = State::where('code', $code)->firstOrFail();
    //     $universities = $state->universities()->get();

    //     return response()->json([
    //         'success' => true,
    //         'state_name' => $state->name,
    //         'data' => $universities
    //     ]);
    // }

    public function getResidencies(Request $request, $code): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $state = State::where('code', $code)->first();

        if (!$state) {
            return response()->json([
                'status' => 'error',
                'message' => 'State not found',
            ], 404);
        }

        $query = $state->residencies();

        if ($search !== '') {
            $query->where('program_name', 'like', '%' . $search . '%');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Residencies retrieved successfully.',
            'status' => 'success',
            'state_name' => $state->name,
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
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    // public function getResidencies($code): JsonResponse
    // {
    //     $state = State::where('code', $code)->firstOrFail();
    //     $residencies = $state->residencies()->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $residencies
    //     ]);
    // }

    public function getFacilities(Request $request, $code): JsonResponse
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $state = State::where('code', $code)->first();

        if (!$state) {
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
            return $q->where('name', 'like', '%' . $search . '%');
        });

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Facilities retrieved successfully.',
            'status' => 'success',
            'state_name' => $state->name,
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
            ],
        ], 200);
    }

    // public function getFacilities($code, Request $request): JsonResponse
    // {
    //     $type = $request->query('type');
    //     $state = State::where('code', $code)->firstOrFail();

    //     $facilities = $state->facilities()
    //         ->when($type, function ($query) use ($type) {
    //             return $query->where('type', $type);
    //         })->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $facilities
    //     ]);
    // }


    public function getJobs($code, Request $request): JsonResponse
    {
        $category = $request->query('category');
        $state = State::where('code', $code)->firstOrFail();

        $jobs = $state->jobs()
            ->when($category, function ($query) use ($category) {
                return $query->where('category', $category);
            })->get();

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }
}
