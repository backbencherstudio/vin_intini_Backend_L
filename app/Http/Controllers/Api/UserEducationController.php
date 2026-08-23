<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Education;
use App\Models\Institution;
use App\Models\Skill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserEducationController extends Controller
{
    public function institutionSuggestions(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = Institution::query()
            ->select(['id', 'name', 'logo', 'type', 'state', 'country', 'website']);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%")
                ->orderByRaw('
                CASE
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ', [$search, $search.'%']);
        }

        $institutions = $query->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $institutions->count(),
            'data' => $institutions,
        ]);
    }

    public function index(Request $request)
    {
        $isOwnEducation = true;

        $educations = Education::query()
            ->where('user_id', $request->user()->id)
            ->with('institution:id,name')
            ->orderByDesc('start_year')
            ->orderByDesc('start_month')
            ->get();

        return response()->json([
            'status' => 'success',
            'is_own_education' => $isOwnEducation,
            'data' => $educations,
        ]);
    }

    public function showEducationByUserId($identifier)
    {
        $viewer = auth('api')->user();

        $targetUser = User::with('profile:user_id,privacy_profile_activity')
            ->where('username', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (! $targetUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $actualUserId = $targetUser->id;
        $isOwnEducation = $viewer && $viewer->id == $actualUserId;

        $privacy = $targetUser->profile->privacy_profile_activity ?? 'everyone';
        $canSee = $isOwnEducation;

        if (! $canSee) {
            if ($privacy === 'everyone') {
                $canSee = true;
            } elseif ($privacy === 'only_connected' && $viewer) {
                $isConnected = Connection::where('status', Connection::STATUS_ACCEPTED)
                    ->where(function ($q) use ($viewer, $actualUserId) {
                        $q->where(function ($q1) use ($viewer, $actualUserId) {
                            $q1->where('sender_id', $viewer->id)->where('receiver_id', $actualUserId);
                        })->orWhere(function ($q2) use ($viewer, $actualUserId) {
                            $q2->where('sender_id', $actualUserId)->where('receiver_id', $viewer->id);
                        });
                    })->exists();

                if ($isConnected) {
                    $canSee = true;
                }
            }
        }

        if (! $canSee) {
            return response()->json([
                'status' => 'success',
                'is_own_education' => $isOwnEducation,
                'is_private_profile' => true,
                'data' => [],
            ]);
        }

        $educations = Education::query()
            ->where('user_id', $actualUserId)
            ->with('institution:id,name')
            ->orderByDesc('start_year')
            ->orderByDesc('start_month')
            ->get();

        if ($educations->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No education found for this user',
                'is_own_education' => $isOwnEducation,
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'is_own_education' => $isOwnEducation,
            'data' => $educations,
        ]);
    }

    // niaz's code================================
    // public function showEducationByUserId($id)
    // {
    //     $isOwnEducation = auth()->check() && auth()->id() == $id;

    //     $educations = Education::query()
    //         ->where('user_id', $id)
    //         ->with('institution:id,name')
    //         ->orderByDesc('start_year')
    //         ->orderByDesc('start_month')
    //         ->get();

    //     if ($educations->isEmpty()) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'No education found for this user',
    //             'is_own_education' => $isOwnEducation,
    //             'data' => [],
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'is_own_education' => $isOwnEducation,
    //         'data' => $educations,
    //     ]);
    // }
    // ===========================================

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'nullable|integer|exists:institutions,id',
            'institution' => 'required_without:institution_id|nullable|string|max:255',
            'degree' => 'required|string|max:255',
            'field_study' => 'nullable|string|max:255',
            'start_month' => 'required|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'start_year' => 'required|integer|min:1900|max:'.(date('Y') + 10),
            'is_current' => 'required|boolean',
            'end_month' => 'required_if:is_current,false,0|nullable|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'end_year' => 'required_if:is_current,false,0|nullable|integer|min:1900|max:'.(date('Y') + 10),
            'grade' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'activities' => 'nullable|string',
            'skills' => 'nullable|array|max:5',
            'skills.*' => 'string|distinct',
        ]);

        $startDate = Carbon::parse($validated['start_month'].' '.$validated['start_year'])->startOfMonth();
        if (! $validated['is_current']) {
            $endDate = Carbon::parse($validated['end_month'].' '.$validated['end_year'])->startOfMonth();

            if ($endDate->lt($startDate)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'End date cannot be earlier than start date.',
                ], 422);
            }
        }

        $institution = $this->resolveInstitution($validated['institution_id'] ?? null, $validated['institution'] ?? null);
        $skillIds = $this->resolveSkillIds($validated['skills'] ?? []);
        $isCurrent = (bool) $validated['is_current'];

        $education = Education::create([
            'user_id' => $request->user()->id,
            'institution_id' => $institution->id,
            'degree' => $validated['degree'],
            'field_study' => $validated['field_study'] ?? null,
            'start_month' => $validated['start_month'],
            'start_year' => $validated['start_year'],
            'end_month' => $isCurrent ? null : $validated['end_month'],
            'end_year' => $isCurrent ? null : $validated['end_year'],
            'grade' => $validated['grade'] ?? null,
            'description' => $validated['description'] ?? null,
            'activities' => $validated['activities'] ?? null,
            'is_current' => $isCurrent,
            'skills_id' => $skillIds,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Education added successfully',
            'data' => $education->load('institution'),
        ], 201);
    }

    public function edit(Request $request, string $id)
    {
        $education = Education::query()
            ->where('user_id', $request->user()->id)
            ->with('institution')
            ->find($id);

        if (! $education) {
            return response()->json([
                'status' => 'error',
                'message' => 'Education not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $education,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $education = Education::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $education) {
            return response()->json([
                'status' => 'error',
                'message' => 'Education not found',
            ], 404);
        }

        $validated = $request->validate([
            'institution_id' => 'nullable|integer|exists:institutions,id',
            'institution' => 'nullable|string|max:255',
            'degree' => 'sometimes|required|string|max:255',
            'field_study' => 'nullable|string|max:255',
            'start_month' => 'sometimes|required|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'start_year' => 'sometimes|required|digits:4',
            'is_current' => 'sometimes|required|boolean',
            'end_month' => 'required_if:is_current,false,0|nullable|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'end_year' => 'required_if:is_current,false,0|nullable|digits:4',
            'grade' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'activities' => 'nullable|string',
            'skills' => 'nullable|array|max:10',
            'skills.*' => 'string|distinct',
        ]);

        $updateData = $request->only([
            'degree',
            'field_study',
            'start_month',
            'start_year',
            'grade',
            'description',
            'activities',
            'is_current',
        ]);

        if ($request->filled('institution_id')) {
            $updateData['institution_id'] = $validated['institution_id'];
        } elseif ($request->filled('institution')) {
            $institution = Institution::firstOrCreate([
                'name' => trim($validated['institution']),
            ]);
            $updateData['institution_id'] = $institution->id;
        }

        $isCurrent = $request->has('is_current') ? (bool) $validated['is_current'] : $education->is_current;

        if ($isCurrent) {
            $updateData['end_month'] = null;
            $updateData['end_year'] = null;
        } else {
            if ($request->has('end_month')) {
                $updateData['end_month'] = $validated['end_month'];
            }
            if ($request->has('end_year')) {
                $updateData['end_year'] = $validated['end_year'];
            }
        }

        $sMonth = $updateData['start_month'] ?? $education->start_month;
        $sYear = $updateData['start_year'] ?? $education->start_year;
        $eMonth = $updateData['end_month'] ?? $education->end_month;
        $eYear = $updateData['end_year'] ?? $education->end_year;

        if (! $isCurrent && $sMonth && $sYear && $eMonth && $eYear) {
            $start = Carbon::parse("$sMonth $sYear")->startOfMonth();
            $end = Carbon::parse("$eMonth $eYear")->startOfMonth();

            if ($end->lt($start)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'End date cannot be earlier than start date.',
                ], 422);
            }
        }

        if ($request->has('skills')) {
            $updateData['skills_id'] = $this->resolveSkillIds($validated['skills']);
        }

        $education->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Education updated successfully',
            'data' => $education->fresh()->load('institution'),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $education = Education::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $education) {
            return response()->json([
                'status' => 'error',
                'message' => 'Education not found',
            ], 404);
        }

        $education->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Education deleted successfully',
        ]);
    }

    private function resolveInstitution(?int $institutionId, ?string $institutionName): Institution
    {
        if ($institutionId) {
            return Institution::query()->findOrFail($institutionId);
        }

        $institutionName = trim((string) $institutionName);
        if ($institutionName === '') {
            throw ValidationException::withMessages([
                'institution' => ['The institution field is required.'],
            ]);
        }

        return Institution::firstOrCreate([
            'name' => $institutionName,
        ]);
    }

    /**
     * @param  array<int, string>  $skills
     * @return array<int, int>
     */
    private function resolveSkillIds(array $skills): array
    {
        $skillIds = [];

        foreach ($skills as $skillName) {
            $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
            $skillIds[] = $skill->id;
        }

        return $skillIds;
    }
}
