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
                    'job_type' => $latestExperience?->employment_type,
                    'period' => $this->formatCompanyPeriod($companyExperiences),
                    'summary' => $latestExperience?->employment_type && $this->formatCompanyPeriod($companyExperiences)
                        ? $latestExperience->employment_type . ' • ' . $this->formatCompanyPeriod($companyExperiences)
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
                ? $startingDate . ' • ' . $statusLabel . ' • ' . $totalTime
                : $startingDate . ' • ' . $endingDate . ' • ' . $totalTime
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
            $parts[] = $years . ' year' . ($years === 1 ? '' : 's');
        }

        if ($remainingMonths > 0) {
            $parts[] = $remainingMonths . ' month' . ($remainingMonths === 1 ? '' : 's');
        }

        if ($parts === []) {
            return 'Less than a month';
        }

        return implode(' ', $parts);
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

        $startDate = Carbon::parse($request->start_month . ' ' . $request->start_year)->startOfMonth();
        $endDate = null;
        if (! $request->is_current && $request->end_month && $request->end_year) {
            $endDate = Carbon::parse($request->end_month . ' ' . $request->end_year)->startOfMonth();
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
}
