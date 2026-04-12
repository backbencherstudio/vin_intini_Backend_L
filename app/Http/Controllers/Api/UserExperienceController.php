<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Experience;
use App\Models\Skill;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserExperienceController extends Controller
{
    public function index()
    {
        $experiences = Experience::where('user_id', auth()->id())->with('company')->get();
        return response()->json([
            'status' => 'success',
            'data' => $experiences
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

        $startDate = Carbon::parse($request->start_month . ' ' . $request->start_year)->startOfMonth();
        $endDate = null;
        if (!$request->is_current && $request->end_month && $request->end_year) {
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
            'data' => $experience->load('company')
        ], 201);
    }
}
