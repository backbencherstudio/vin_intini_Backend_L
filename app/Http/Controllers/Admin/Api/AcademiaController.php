<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademiaUniversity;
use Illuminate\Http\Request;

class AcademiaController extends Controller
{

    public function indexUniversities(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $query = AcademiaUniversity::query()
            ->with('state');

        if ($request->filled('search')) {
            $query->where(
                'name',
                'LIKE',
                '%' . $request->input('search') . '%'
            );
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        $universities = $query
            ->paginate($perPage)
            ->withQueryString();

        $data = collect($universities->items())
            ->map(function ($university) {
                return [
                    'id' => $university->id,
                    'university_name' => $university->name,
                    'location' => $university->location,
                    'phone' => $university->phone,
                    'state' => $university->state?->name,
                    'psychology_degrees' => $university->psychology_degrees,
                    'counseling_degrees' => $university->counseling_degrees,
                    'neuroscience_degrees' => $university->neuroscience_degrees,
                    'map_pin' => [
                        'latitude' => $university->latitude,
                        'longitude' => $university->longitude,
                    ],

                    'website' => $university->website,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Universities fetched successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $universities->currentPage(),
                'per_page' => $universities->perPage(),
                'total' => $universities->total(),
                'last_page' => $universities->lastPage(),
                'from' => $universities->firstItem(),
                'to' => $universities->lastItem(),
                'has_more_pages' => $universities->hasMorePages(),
                'next_page_url' => $universities->nextPageUrl(),
                'prev_page_url' => $universities->previousPageUrl(),
            ],
        ]);
    }


    public function storeUniversity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',

            'psychology_degrees' => 'nullable|array',
            'psychology_degrees.*' => 'string',

            'counseling_degrees' => 'nullable|array',
            'counseling_degrees.*' => 'string',

            'neuroscience_degrees' => 'nullable|array',
            'neuroscience_degrees.*' => 'string',

            'has_online_options' => 'nullable|boolean',

            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:500',
        ]);

        $university = AcademiaUniversity::create([
            'name' => $validated['name'],
            'state_id' => $validated['state_id'],

            'psychology_degrees' => $validated['psychology_degrees'] ?? [],
            'counseling_degrees' => $validated['counseling_degrees'] ?? [],
            'neuroscience_degrees' => $validated['neuroscience_degrees'] ?? [],

            'has_online_options' => $request->boolean('has_online_options'),

            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,

            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);


        $university->load('state');

        return response()->json([
            'success' => true,
            'message' => 'New University added successfully.',

            'data' => [
                'id' => $university->id,
                'university_name' => $university->name,
                'state' => $university->state?->name,

                'psychology_degrees' => $university->psychology_degrees,
                'counseling_degrees' => $university->counseling_degrees,
                'neuroscience_degrees' => $university->neuroscience_degrees,

                'has_online_options' => $university->has_online_options,

                'map_pin' => [
                    'latitude' => $university->latitude,
                    'longitude' => $university->longitude,
                ],

                'phone' => $university->phone,
                'location' => $university->location,
                'website' => $university->website,
            ],
        ], 201);
    }


    public function updateUniversity(Request $request, $id)
    {
        $university = AcademiaUniversity::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'state_id' => 'sometimes|required|exists:states,id',

            'psychology_degrees' => 'sometimes|nullable|array',
            'psychology_degrees.*' => 'string',

            'counseling_degrees' => 'sometimes|nullable|array',
            'counseling_degrees.*' => 'string',

            'neuroscience_degrees' => 'sometimes|nullable|array',
            'neuroscience_degrees.*' => 'string',

            'has_online_options' => 'sometimes|boolean',

            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',

            'phone' => 'sometimes|nullable|string|max:50',
            'location' => 'sometimes|nullable|string|max:500',
            'website' => 'sometimes|nullable|url|max:500',
        ]);



        if ($request->has('name')) {
            $university->name = $validated['name'];
        }

        if ($request->has('state_id')) {
            $university->state_id = $validated['state_id'];
        }

        if ($request->has('psychology_degrees')) {
            $university->psychology_degrees =
                $validated['psychology_degrees'] ?? [];
        }

        if ($request->has('counseling_degrees')) {
            $university->counseling_degrees =
                $validated['counseling_degrees'] ?? [];
        }

        if ($request->has('neuroscience_degrees')) {
            $university->neuroscience_degrees =
                $validated['neuroscience_degrees'] ?? [];
        }

        if ($request->has('has_online_options')) {
            $university->has_online_options =
                $request->boolean('has_online_options');
        }

        if ($request->has('latitude')) {
            $university->latitude = $validated['latitude'];
        }

        if ($request->has('longitude')) {
            $university->longitude = $validated['longitude'];
        }

        if ($request->has('phone')) {
            $university->phone = $validated['phone'];
        }

        if ($request->has('location')) {
            $university->location = $validated['location'];
        }

        if ($request->has('website')) {
            $university->website = $validated['website'];
        }

        $university->save();

        $university->load('state');

        return response()->json([
            'success' => true,
            'message' => 'University updated successfully.',

            'data' => [
                'id' => $university->id,
                'university_name' => $university->name,
                'state' => $university->state?->name,

                'psychology_degrees' => $university->psychology_degrees,
                'counseling_degrees' => $university->counseling_degrees,
                'neuroscience_degrees' => $university->neuroscience_degrees,

                'has_online_options' => $university->has_online_options,

                'map_pin' => [
                    'latitude' => $university->latitude,
                    'longitude' => $university->longitude,
                ],

                'phone' => $university->phone,
                'location' => $university->location,
                'website' => $university->website,
            ],
        ]);
    }


    public function destroyUniversity($id)
    {
        $university = AcademiaUniversity::find($id);

        if (!$university) {
            return response()->json([
                'success' => false,
                'message' => 'University not found.',
            ], 404);
        }

        $university->delete();

        return response()->json([
            'success' => true,
            'message' => 'University deleted successfully.',
        ], 200);
    }
}
