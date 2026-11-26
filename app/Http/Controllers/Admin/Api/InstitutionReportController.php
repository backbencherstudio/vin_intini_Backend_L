<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Institution;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionReportController extends Controller
{
    public function institutionReport(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $institutions = Institution::query()
            ->when($search, function ($query, $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($status === 'pending', function ($query): void {
                $query->where('is_verified', false);
            })
            ->when($status === 'verified', function ($query): void {
                $query->where('is_verified', true);
            })
            ->withCount([
                'educations as total_students' => function ($query): void {
                    $query->select(DB::raw('count(distinct(user_id))'));
                },
                'educations as present_students' => function ($query): void {
                    $query->where('is_current', true)
                        ->select(DB::raw('count(distinct(user_id))'));
                },
                'educations as completed_programs' => function ($query): void {
                    $query->where('is_current', false)
                        ->select(DB::raw('count(distinct(user_id))'));
                },
            ])
            ->when(
                $status === 'pending',
                fn ($query) => $query->orderBy('created_at', 'desc'),
                fn ($query) => $query->orderBy('is_verified', 'asc')->orderBy('total_students', 'desc')
            )
            ->paginate(20);

        $data = $institutions->getCollection()->map(fn ($institution, $index): array => [
            'sl_no' => (($institutions->currentPage() - 1) * $institutions->perPage()) + $index + 1,
            'id' => $institution->id,
            'institution_name' => $institution->name,
            'is_verified' => (bool) $institution->is_verified,
            'total_students' => $institution->total_students,
            'completed_programs' => $institution->completed_programs,
            'present_students' => $institution->present_students,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'pending_count' => Institution::where('is_verified', false)->count(),
                'verified_count' => Institution::where('is_verified', true)->count(),
            ],
            'pagination' => [
                'current_page' => $institutions->currentPage(),
                'per_page' => $institutions->perPage(),
                'total' => $institutions->total(),
                'last_page' => $institutions->lastPage(),
            ],
        ]);
    }

    public function showStudents(Request $request, $id)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $institution = Institution::findOrFail($id);

        $search = $request->input('search');
        $status = $request->input('status');

        $educations = Education::where('institution_id', $id)
            ->with('user')
            ->whereHas('user', function ($q) use ($search): void {
                if ($search) {
                    $q->where(
                        DB::raw("CONCAT(first_name, ' ', last_name)"),
                        'like',
                        "%{$search}%"
                    )->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->when($status, function ($q) use ($status) {
                if ($status === 'present') {
                    return $q->where('is_current', true);
                }

                if ($status === 'completed') {
                    return $q->where('is_current', false);
                }

                return $q->whereRaw('1=0');
            })
            ->latest()
            ->paginate($perPage);

        $data = $educations->getCollection()->map(function ($education, $index) use ($educations): array {

            $user = $education->user;

            $studentName = $user
                ? trim($user->first_name.' '.$user->last_name)
                : null;

            $status = $education->is_current ? 'Present' : 'Completed';

            $academicPeriod = $education->is_current
                ? $education->start_month.' '.$education->start_year.' — Present'
                : $education->start_month.' '.$education->start_year
                .' — '.
                $education->end_month.' '.$education->end_year;

            return [
                'sl_no' => (($educations->currentPage() - 1) * $educations->perPage()) + $index + 1,
                'student_image' => $user?->profile_image_url,
                'student_name' => $studentName,
                'student_email' => $user?->email,
                'program' => $education->degree,
                'field' => $education->field_study,
                'academic_period' => $academicPeriod,
                'status' => $status,
            ];
        });

        return response()->json([
            'success' => true,

            'institution' => [
                'id' => $institution->id,
                'logo' => $institution->logo,
                'name' => $institution->name,
            ],

            'data' => $data,

            'pagination' => [
                'current_page' => $educations->currentPage(),
                'per_page' => $educations->perPage(),
                'total' => $educations->total(),
                'last_page' => $educations->lastPage(),
                'from' => $educations->firstItem(),
                'to' => $educations->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'is_verified' => 'sometimes|boolean',
        ]);

        $cleanName = preg_replace('/\s+/', ' ', trim($validated['name'])) ?? trim($validated['name']);

        $duplicate = Institution::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'An institution with this name already exists.',
            ], 422);
        }

        $institution = Institution::create([
            'name' => $cleanName,
            'is_verified' => $request->has('is_verified') ? (bool) $validated['is_verified'] : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Institution added successfully.',
            'data' => $institution,
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $institution = Institution::findOrFail($id);
        $institution->update([
            'is_verified' => (bool) $validated['is_verified'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $institution->is_verified
                ? 'Institution approved and added to public suggestions.'
                : 'Institution hidden from public suggestions.',
            'data' => $institution,
        ]);
    }

    public function rename(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'is_verified' => 'sometimes|boolean',
        ]);

        $institution = Institution::findOrFail($id);
        $cleanName = preg_replace('/\s+/', ' ', trim($validated['name'])) ?? trim($validated['name']);

        $duplicate = Institution::query()
            ->where('id', '!=', $institution->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'An institution with this name already exists. Please merge into it instead.',
            ], 422);
        }

        $updateData = ['name' => $cleanName];
        if (array_key_exists('is_verified', $validated)) {
            $updateData['is_verified'] = (bool) $validated['is_verified'];
        }

        $institution->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Institution updated successfully.',
            'data' => $institution,
        ]);
    }

    public function merge(Request $request, $id)
    {
        $validated = $request->validate([
            'target_institution_id' => 'required|integer|exists:institutions,id',
        ]);

        $source = Institution::findOrFail($id);
        $target = Institution::findOrFail($validated['target_institution_id']);

        if ($source->id === $target->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot merge an institution into itself.',
            ], 422);
        }

        DB::transaction(function () use ($source, $target): void {
            Education::where('institution_id', $source->id)
                ->update(['institution_id' => $target->id]);

            UserProfile::where('current_institute_id', $source->id)
                ->update(['current_institute_id' => $target->id]);

            $source->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Merged \"{$source->name}\" into \"{$target->name}\".",
        ]);
    }

    public function destroy($id)
    {
        $institution = Institution::findOrFail($id);

        $hasStudents = Education::where('institution_id', $institution->id)->exists()
            || UserProfile::where('current_institute_id', $institution->id)->exists();

        if ($hasStudents) {
            return response()->json([
                'success' => false,
                'message' => 'This institution has linked students. Merge it into a verified institution instead.',
            ], 422);
        }

        $institution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.',
        ]);
    }
}
