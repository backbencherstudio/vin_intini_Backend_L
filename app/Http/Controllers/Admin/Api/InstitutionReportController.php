<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
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
                }
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
}
