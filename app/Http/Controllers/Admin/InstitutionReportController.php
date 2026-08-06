<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Education;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;

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
                'educations as completed_students' => function ($query) {
                    $query->where('is_current', false)
                        ->select(DB::raw('count(distinct(user_id))'));
                }
            ])
            ->orderBy('total_students', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.institution_report.index', compact('institutions'));
    }


    public function showStudents(Request $request, $id)
    {
        $institution = Institution::findOrFail($id);

        $search = $request->input('search');
        $status = $request->input('status'); // present or completed

        $educations = Education::where('institution_id', $id)
            ->with('user')
            ->whereHas('user', function ($q) use ($search) {
                if ($search) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                return $q->where('is_current', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.institution_report.students', compact('institution', 'educations'));
    }
}
