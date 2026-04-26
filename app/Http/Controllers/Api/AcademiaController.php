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

    public function getStateDetails($id): JsonResponse
    {
        $state = State::where('id', $id)
            ->with(['universities', 'residencies', 'facilities', 'jobs'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new StateResource($state)
        ]);
    }

    public function getUniversities($id): JsonResponse
    {
        $state = State::where('id', $id)->firstOrFail();
        $universities = $state->universities()->get();

        return response()->json([
            'success' => true,
            'state_name' => $state->name,
            'data' => $universities
        ]);
    }

    public function getResidencies($id): JsonResponse
    {
        $state = State::where('id', $id)->firstOrFail();
        $residencies = $state->residencies()->get();

        return response()->json([
            'success' => true,
            'data' => $residencies
        ]);
    }

    public function getFacilities($id, Request $request): JsonResponse
    {
        $type = $request->query('type');
        $state = State::where('id', $id)->firstOrFail();

        $facilities = $state->facilities()
            ->when($type, function ($query) use ($type) {
                return $query->where('type', $type);
            })->get();

        return response()->json([
            'success' => true,
            'data' => $facilities
        ]);
    }


    public function getJobs($id, Request $request): JsonResponse
    {
        $category = $request->query('category');
        $state = State::where('id', $id)->firstOrFail();

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
