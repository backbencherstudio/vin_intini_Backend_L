<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionReportController extends Controller
{
    public function institutionReport(Request $request)
    {
        $search = $request->input('search');

        $institutions = Institution::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withCount([
                'educations as total_students' => function ($query) {
                    $query->select(DB::raw('count(distinct(user_id))'));
                },
                'educations as present_students' => function ($query) {
                    $query->where('is_current', true)
                        ->select(DB::raw('count(distinct(user_id))'));
                },
                'educations as completed_programs' => function ($query) {
                    $query->where('is_current', false)
                        ->select(DB::raw('count(distinct(user_id))'));
                },
            ])
            ->orderBy('total_students', 'desc')
            ->paginate(20);

        $data = $institutions->getCollection()->map(function ($institution, $index) use ($institutions) {
            return [
                'sl_no' => (($institutions->currentPage() - 1) * $institutions->perPage()) + $index + 1,
                'id' => $institution->id,
                'institution_name' => $institution->name,
                'total_students' => $institution->total_students,
                'completed_programs' => $institution->completed_programs,
                'present_students' => $institution->present_students,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
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
        $institution = Institution::findOrFail($id);

        $search = $request->input('search');
        $status = $request->input('status');

        $educations = Education::where('institution_id', $id)
            ->with('user')
            ->whereHas('user', function ($q) use ($search) {
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

                return $q;
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        $data = $educations->getCollection()->map(function ($education, $index) use ($educations) {

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
                'student_image' => $user->profile_image_url,
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
}
