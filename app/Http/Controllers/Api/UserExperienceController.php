<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Experience;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserExperienceController extends Controller
{
    public function index(Request $request)
    {
        $experiences = Experience::query()
            ->where('user_id', $request->user()->id)
            ->with('company')
            ->orderByDesc('start_date')
            ->get();

        if ($experiences->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No experiences found',
                'data' => [],
            ]);
        }

        $grouped = $experiences
            ->groupBy('company_id')
            ->map(function ($companyExperiences) {
                $company = $companyExperiences->first()->company;
                $latestExperience = $companyExperiences->first();

                $experiences = $companyExperiences->values()->map(function ($experience) {
                    return $this->formatExperience($experience);
                });

                return [
                    'company' => $company,
                    'company_name' => $company?->name,
                    'job_type' => $latestExperience?->employment_type,
                    'period' => $this->formatCompanyPeriod($companyExperiences),
                    'summary' => $latestExperience?->employment_type && $this->formatCompanyPeriod($companyExperiences)
                        ? $latestExperience->employment_type.' • '.$this->formatCompanyPeriod($companyExperiences)
                        : null,
                    'experiences' => $experiences,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $grouped,
        ]);
    }

    private function formatExperience(Experience $experience): array
    {
        $companyName = $experience->company?->name;
        $experience->unsetRelation('company');

        $endDate = $experience->is_current ? now() : $experience->end_date;
        $startingDate = $experience->start_date?->format('M Y');
        $endingDate = $experience->is_current
            ? 'Present'
            : $experience->end_date?->format('M Y');
        $statusLabel = $experience->is_current ? 'Still working' : 'Completed';
        $totalTime = $this->formatDuration($experience->start_date, $endDate);

        return [
            ...$experience->toArray(),
            'experience_title' => $experience->title,
            'company_name' => $companyName,
            'start_date' => $startingDate,
            'starting_date' => $startingDate,
            'end_date' => $endingDate,
            'ending_date' => $endingDate,
            'status' => $experience->is_current ? 'Present' : 'Ended',
            'status_label' => $statusLabel,
            'duration' => $totalTime,
            'total_time' => $totalTime,
            'timeline' => $startingDate && $totalTime
                ? $experience->is_current
                ? $startingDate.' • '.$statusLabel.' • '.$totalTime
                : $startingDate.' • '.$endingDate.' • '.$totalTime
                : null,
        ];
    }

    private function formatCompanyPeriod(Collection $companyExperiences): ?string
    {
        $startDate = $companyExperiences->last()->start_date;
        $latestExperience = $companyExperiences->first();
        $endDate = $latestExperience->is_current ? now() : $latestExperience->end_date;

        return $this->formatDuration($startDate, $endDate);
    }

    private function formatDuration(?Carbon $startDate, ?Carbon $endDate): ?string
    {
        if (! $startDate || ! $endDate) {
            return null;
        }

        $months = $startDate->diffInMonths($endDate);
        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;
        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' year'.($years === 1 ? '' : 's');
        }

        if ($remainingMonths > 0) {
            $parts[] = $remainingMonths.' month'.($remainingMonths === 1 ? '' : 's');
        }

        if ($parts === []) {
            return 'Less than a month';
        }

        return implode(' ', $parts);
    }

    public function companySuggestions(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $limit = $validated['limit'] ?? 10;

        $companies = Company::query()
            ->select(['id', 'name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $companies,
        ]);
    }

    public function skillSuggestions(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $limit = $validated['limit'] ?? 10;

        $skills = Skill::query()
            ->select(['id', 'name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $skills,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'company_name' => 'required|string',
            'start_month' => 'required', // e.g., "January"
            'start_year' => 'required',  // e.g., "2024"
            'skills' => 'array',         // e.g., ["PHP", "Laravel"]
        ]);

        $startDate = Carbon::parse($request->start_month.' '.$request->start_year)->startOfMonth();
        $endDate = null;
        if (! $request->is_current && $request->end_month && $request->end_year) {
            $endDate = Carbon::parse($request->end_month.' '.$request->end_year)->startOfMonth();
        }

        $company = Company::firstOrCreate(['name' => trim($request->company_name)]);

        $skillIds = [];
        if ($request->has('skills')) {
            foreach ($request->skills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
                $skillIds[] = $skill->id;
            }
        }

        $experience = Experience::create([
            'user_id' => auth()->id(),
            'company_id' => $company->id,
            'title' => $request->title,
            'employment_type' => $request->employment_type,
            'location' => $request->location,
            'location_type' => $request->location_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => $request->is_current ?? false,
            'description' => $request->description,
            'skills_id' => $skillIds,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Experience added successfully',
            'data' => $experience->load('company'),
        ], 201);
    }

    public function edit(Request $request, $id)
    {
        $experience = Experience::query()
            ->where('user_id', $request->user()->id)
            ->with('company')
            ->find($id);

        if (! $experience) {
            return response()->json([
                'status' => 'error',
                'message' => 'Experience not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                ...$experience->toArray(),
                'company_name' => $experience->company?->name,
                'start_month' => $experience->start_date?->format('F'),
                'start_year' => $experience->start_date?->format('Y'),
                'end_month' => $experience->end_date?->format('F'),
                'end_year' => $experience->end_date?->format('Y'),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $experience = Experience::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $experience) {
            return response()->json([
                'status' => 'error',
                'message' => 'Experience not found',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'company_name' => 'sometimes|required|string',
            'employment_type' => 'nullable|string',
            'location' => 'nullable|string',
            'location_type' => 'nullable|string',
            'start_month' => 'sometimes|required|string',
            'start_year' => 'sometimes|required|digits:4',
            'end_month' => 'nullable|string',
            'end_year' => 'nullable|digits:4',
            'is_current' => 'nullable|boolean',
            'description' => 'nullable|string',
            'skills' => 'nullable|array',
            'skills.*' => 'string',
        ]);

        $updateData = [
            'title' => $validated['title'] ?? $experience->title,
            'employment_type' => $validated['employment_type'] ?? $experience->employment_type,
            'location' => $validated['location'] ?? $experience->location,
            'location_type' => $validated['location_type'] ?? $experience->location_type,
            'description' => $validated['description'] ?? $experience->description,
        ];

        if (array_key_exists('company_name', $validated)) {
            $company = Company::firstOrCreate(['name' => trim($validated['company_name'])]);
            $updateData['company_id'] = $company->id;
        }

        if (array_key_exists('start_month', $validated) && array_key_exists('start_year', $validated)) {
            $updateData['start_date'] = Carbon::parse($validated['start_month'].' '.$validated['start_year'])->startOfMonth();
        }

        $isCurrent = array_key_exists('is_current', $validated)
            ? (bool) $validated['is_current']
            : $experience->is_current;

        $updateData['is_current'] = $isCurrent;

        if ($isCurrent) {
            $updateData['end_date'] = null;
        } elseif (array_key_exists('end_month', $validated) && array_key_exists('end_year', $validated)) {
            $updateData['end_date'] = Carbon::parse($validated['end_month'].' '.$validated['end_year'])->startOfMonth();
        }

        if (array_key_exists('skills', $validated)) {
            $skillIds = [];

            foreach ($validated['skills'] as $skillName) {
                $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
                $skillIds[] = $skill->id;
            }

            $updateData['skills_id'] = $skillIds;
        }

        $experience->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Experience updated successfully',
            'data' => $experience->fresh()->load('company'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $experience = Experience::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $experience) {
            return response()->json([
                'status' => 'error',
                'message' => 'Experience not found',
            ], 404);
        }

        $experience->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Experience deleted successfully',
        ]);
    }
}
