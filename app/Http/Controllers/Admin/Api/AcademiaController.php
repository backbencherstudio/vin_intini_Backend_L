<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademiaFacility;
use App\Models\AcademiaJob;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaUniversity;
use App\Models\State;
use Illuminate\Http\Request;

class AcademiaController extends Controller
{
    public function indexUniversities(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = AcademiaUniversity::query()
            ->with('state');

        if ($request->filled('search')) {
            $query->where(
                'name',
                'LIKE',
                '%'.$request->input('search').'%'
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

        if (! $university) {
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

        if (! $university) {
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

    public function indexResidencies(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = AcademiaMedicalResidency::with('state');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('program_name', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        $residencies = $query
            ->paginate($perPage)
            ->withQueryString();

        $data = collect($residencies->items())
            ->map(function ($residency) {
                return [
                    'id' => $residency->id,
                    'program_name' => $residency->program_name,
                    'location' => $residency->location,
                    'phone' => $residency->phone,
                    'state' => $residency->state?->name,
                    'degree-types' => $residency->degree_types,
                    'map_pin' => [
                        'latitude' => $residency->latitude,
                        'longitude' => $residency->longitude,
                    ],
                    'website' => $residency->website,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Residencies fetched successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $residencies->currentPage(),
                'per_page' => $residencies->perPage(),
                'total' => $residencies->total(),
                'last_page' => $residencies->lastPage(),
                'from' => $residencies->firstItem(),
                'to' => $residencies->lastItem(),
                'has_more_pages' => $residencies->hasMorePages(),
                'next_page_url' => $residencies->nextPageUrl(),
                'prev_page_url' => $residencies->previousPageUrl(),
            ],
        ]);
    }

    public function storeResidency(Request $request)
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'degree_types' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $degrees = ! empty($validated['degree_types'])
            ? array_map('trim', explode(',', $validated['degree_types']))
            : [];

        $residency = AcademiaMedicalResidency::create([
            'program_name' => $validated['program_name'],
            'state_id' => $validated['state_id'],
            'location' => $validated['location'] ?? null,
            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,
            'degree_types' => $degrees,
            'website' => $validated['website'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $residency->load('state');

        return response()->json([
            'success' => true,
            'message' => 'New Residency Program added successfully.',
            'data' => [
                'id' => $residency->id,
                'program_name' => $residency->program_name,
                'city' => $residency->location,
                'state' => $residency->state?->name,
                'degree_types' => $residency->degree_types,
                'phone' => $residency->phone,
                'map_pin' => [
                    'latitude' => $residency->latitude,
                    'longitude' => $residency->longitude,
                ],
                'website' => $residency->website,
            ],
        ], 201);
    }

    public function updateResidency(Request $request, $id)
    {
        $residency = AcademiaMedicalResidency::findOrFail($id);

        $validated = $request->validate([
            'program_name' => 'sometimes|string|max:255',
            'state_id' => 'sometimes|exists:states,id',
            'location' => 'sometimes|nullable|string|max:255',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'degree_types' => 'sometimes|nullable|string',
            'website' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:255',
        ]);

        if (array_key_exists('degree_types', $validated)) {
            $validated['degree_types'] = ! empty($validated['degree_types'])
                ? array_map('trim', explode(',', $validated['degree_types']))
                : [];
        }

        $residency->update($validated);

        $residency->load('state');

        return response()->json([
            'success' => true,
            'message' => 'Residency Program updated successfully.',
            'data' => [
                'id' => $residency->id,
                'program_name' => $residency->program_name,
                'city' => $residency->location,
                'state' => $residency->state?->name,
                'degree_types' => $residency->degree_types,
                'phone' => $residency->phone,
                'map_pin' => [
                    'latitude' => $residency->latitude,
                    'longitude' => $residency->longitude,
                ],
                'website' => $residency->website,
            ],
        ]);
    }

    public function destroyResidency($id)
    {
        $residency = AcademiaMedicalResidency::find($id);

        if (! $residency) {
            return response()->json([
                'success' => false,
                'message' => 'Residency not found.',
            ], 404);
        }

        $residency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Residency deleted successfully.',
        ], 200);
    }

    public function indexFacilities(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = AcademiaFacility::with('state');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        if ($request->filled('category')) {
            $query->where('type', $request->input('category'));
        }

        $facilities = $query
            ->paginate($perPage)
            ->withQueryString();

        $data = collect($facilities->items())
            ->map(function ($facility) {
                return [
                    'id' => $facility->id,
                    'facility_details' => [
                        'name' => $facility->name,
                        'location' => $facility->location,
                        'phone' => $facility->phone,
                    ],
                    'state' => $facility->state?->name,
                    'category' => $facility->type,
                    'map_pin' => [
                        'latitude' => $facility->latitude,
                        'longitude' => $facility->longitude,
                    ],
                    'website' => $facility->website,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Facilities fetched successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $facilities->currentPage(),
                'per_page' => $facilities->perPage(),
                'total' => $facilities->total(),
                'last_page' => $facilities->lastPage(),
                'from' => $facilities->firstItem(),
                'to' => $facilities->lastItem(),
                'has_more_pages' => $facilities->hasMorePages(),
                'next_page_url' => $facilities->nextPageUrl(),
                'prev_page_url' => $facilities->previousPageUrl(),
            ],
        ]);
    }

    public function storeFacility(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'type' => 'required|in:state_institution,university_hospital,va_facility',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $facility = AcademiaFacility::create([
            'name' => $validated['name'],
            'state_id' => $validated['state_id'],
            'type' => $validated['type'],
            'location' => $validated['location'] ?? null,
            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,
            'website' => $validated['website'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $facility->load('state');

        return response()->json([
            'success' => true,
            'message' => 'New Facility added successfully.',
            'data' => [
                'id' => $facility->id,
                'program_name' => $facility->name,
                'city' => $facility->location,
                'state' => $facility->state?->name,
                'degree_types' => $facility->type,
                'phone' => $facility->phone,
                'map_pin' => [
                    'latitude' => $facility->latitude,
                    'longitude' => $facility->longitude,
                ],
                'website' => $facility->website,
            ],
        ], 201);
    }

    public function updateFacility(Request $request, $id)
    {
        $facility = AcademiaFacility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'state_id' => 'sometimes|exists:states,id',
            'type' => 'sometimes|in:state_institution,university_hospital,va_facility',
            'location' => 'sometimes|nullable|string|max:255',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'website' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:255',
        ]);

        $facility->update($validated);

        $facility->load('state');

        return response()->json([
            'success' => true,
            'message' => 'Facility updated successfully.',
            'data' => [
                'id' => $facility->id,
                'program_name' => $facility->name,
                'city' => $facility->location,
                'state' => $facility->state?->name,
                'degree_types' => $facility->type,
                'phone' => $facility->phone,
                'map_pin' => [
                    'latitude' => $facility->latitude,
                    'longitude' => $facility->longitude,
                ],
                'website' => $facility->website,
            ],
        ]);
    }

    public function destroyFacility($id)
    {
        $facility = AcademiaFacility::find($id);

        if (! $facility) {
            return response()->json([
                'success' => false,
                'message' => 'Facility not found.',
            ], 404);
        }

        $facility->delete();

        return response()->json([
            'success' => true,
            'message' => 'Facility deleted successfully.',
        ], 200);
    }

    public function indexJobs(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = AcademiaJob::with('state');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $jobs = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $data = collect($jobs->items())
            ->map(function ($job) {
                $salaryRange = null;

                if ($job->salary_min !== null && $job->salary_max !== null) {
                    $salaryRange = '$'.
                        number_format($job->salary_min / 1000, 0).'k - $'.
                        number_format($job->salary_max / 1000, 0).'k';
                } elseif ($job->salary_min !== null) {
                    $salaryRange = '$'.
                        number_format($job->salary_min / 1000, 0).'k+';
                } elseif ($job->salary_max !== null) {
                    $salaryRange = 'Up to $'.
                        number_format($job->salary_max / 1000, 0).'k';
                }

                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company_name' => $job->company_name,
                    'location' => $job->location,
                    'state' => $job->state?->name,
                    'category' => $job->category,
                    'job_type' => $job->employment_type,
                    'job_mode' => $job->work_mode,
                    'salary_range' => $salaryRange,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Jobs fetched successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
                'from' => $jobs->firstItem(),
                'to' => $jobs->lastItem(),
                'has_more_pages' => $jobs->hasMorePages(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
        ]);
    }

    public function storeJob(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'category' => 'required|in:state_institution,private_practice',

            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',

            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'employment_type' => 'nullable|string|max:255',
            'work_mode' => 'nullable|string|max:255',
        ]);

        $job = AcademiaJob::create([
            'state_id' => $validated['state_id'],
            'title' => $validated['title'],
            'company_name' => $validated['company_name'],
            'location' => $validated['location'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'category' => $validated['category'],
            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,
            'employment_type' => $validated['employment_type'] ?? null,
            'work_mode' => $validated['work_mode'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'New Job Opening added successfully!',
            'data' => [
                'id' => $job->id,
                'state_id' => $job->state_id,
                'title' => $job->title,
                'company_name' => $job->company_name,
                'location' => $job->location,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'category' => $job->category,
                'latitude' => $job->latitude,
                'longitude' => $job->longitude,
                'employment_type' => $job->employment_type,
                'work_mode' => $job->work_mode,
            ],
        ], 201);
    }

    public function updateJob(Request $request, $id)
    {
        $job = AcademiaJob::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'state_id' => 'sometimes|exists:states,id',
            'location' => 'sometimes|nullable|string|max:255',
            'salary_min' => 'sometimes|nullable|numeric',
            'salary_max' => 'sometimes|nullable|numeric',
            'category' => 'sometimes|in:state_institution,private_practice',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'employment_type' => 'sometimes|nullable|string|max:255',
            'work_mode' => 'sometimes|nullable|string|max:255',
        ]);

        $job->update($validated);

        $job->load('state');

        return response()->json([
            'success' => true,
            'message' => 'Job Opening updated successfully.',
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->company_name,
                'category' => $job->category,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'employment_type' => $job->employment_type,
                'work_mode' => $job->work_mode,
                'city' => $job->location,
                'state' => $job->state?->name,
                'map_pin' => [
                    'latitude' => $job->latitude,
                    'longitude' => $job->longitude,
                ],
            ],
        ]);
    }

    public function destroyJob($id)
    {
        $job = AcademiaJob::find($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.',
        ], 200);
    }

    // For State.....
    public function getState()
    {
        $states = State::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'States retrieved successfully.',
            'data' => $states,
        ]);
    }
}
