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
                    'phone' => $university->phone,
                    'location' => $university->location,
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
}
