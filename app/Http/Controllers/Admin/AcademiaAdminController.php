<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademiaFacility;
use App\Models\AcademiaJob;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaUniversity;
use App\Models\State;
use Illuminate\Http\Request;

class AcademiaAdminController extends Controller
{
    // University List
    public function indexUniversities(Request $request)
    {
        $query = AcademiaUniversity::with('state');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        $data = $query->paginate(20)->withQueryString();
        $states = State::orderBy('name')->get();

        return view('admin.academia.universities.index', compact('data', 'states'));
    }

    public function storeUniversity(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
        ]);

        $psychDegrees = $request->psychology_degrees ? array_map('trim', explode(',', $request->psychology_degrees)) : [];
        $counselingDegrees = $request->counseling_degrees ? array_map('trim', explode(',', $request->counseling_degrees)) : [];
        $neuroDegrees = $request->neuroscience_degrees ? array_map('trim', explode(',', $request->neuroscience_degrees)) : [];

        AcademiaUniversity::create([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'has_online_options' => $request->has('has_online_options'),
            'psychology_degrees' => $psychDegrees,
            'counseling_degrees' => $counselingDegrees,
            'neuroscience_degrees' => $neuroDegrees,
            'website' => $request->website ?? null,
            'phone' => $request->phone ?? null,
            'location' => $request->location ?? null,
        ]);

        return redirect()->back()->with('success', 'New University added successfully!');
    }

    // Update University
    public function updateUniversity(Request $request, $id)
    {
        $uni = AcademiaUniversity::findOrFail($id);

        // String to Array Conversion
        $psych = $request->psychology_degrees
            ? array_filter(array_map('trim', explode(',', $request->psychology_degrees)))
            : [];

        $neuro = $request->neuroscience_degrees
            ? array_filter(array_map('trim', explode(',', $request->neuroscience_degrees)))
            : [];

        $counseling = $request->counseling_degrees
            ? array_filter(array_map('trim', explode(',', $request->counseling_degrees)))
            : [];

        $uni->update([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'psychology_degrees' => $psych,
            'neuroscience_degrees' => $neuro,
            'counseling_degrees' => $counseling,
            'has_online_options' => $request->has('has_online_options'),
            'phone' => $request->phone ?? null,
            'location' => $request->location ?? null,
            'website' => $request->website ?? null,
        ]);

        return redirect()->back()->with('success', 'University updated successfully!');
    }

    public function destroyUniversity($id)
    {
        $uni = AcademiaUniversity::findOrFail($id);
        $uni->delete();

        return redirect()->back()->with('success', 'University deleted successfully!');
    }

    // Facility/Hospital List
    public function indexFacilities(Request $request)
    {
        $query = AcademiaFacility::with('state');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $data = $query->paginate(20)->withQueryString();
        $states = State::orderBy('name')->get();

        return view('admin.academia.facilities.index', compact('data', 'states'));
    }

    public function updateFacility(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'type' => 'required|in:state_institution,university_hospital,va_facility',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $facility = AcademiaFacility::findOrFail($id);

        $facility->update([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'location' => $request->location,
            'type' => $request->type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'website' => $request->website ?? null,
            'phone' => $request->phone ?? null,
        ]);

        return redirect()->back()->with('success', 'Facility updated successfully!');
    }

    public function storeFacility(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'type' => 'required',
            'phone' => 'nullable|string|max:255',
        ]);

        AcademiaFacility::create($request->all());

        return redirect()->back()->with('success', 'New Facility added successfully!');
    }

    public function destroyFacility($id)
    {
        $facility = AcademiaFacility::findOrFail($id);
        $facility->delete();

        return redirect()->back()->with('success', 'Facility deleted successfully!');
    }

    // Residencies List
    public function indexResidencies(Request $request)
    {
        $query = AcademiaMedicalResidency::with('state');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('program_name', 'LIKE', "%$search%")
                    ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        $data = $query->paginate(20)->withQueryString();
        $states = State::orderBy('name')->get();

        return view('admin.academia.residencies.index', compact('data', 'states'));
    }

    public function storeResidency(Request $request)
    {
        $request->validate([
            'program_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'phone' => 'nullable|string|max:255',
        ]);

        $degrees = $request->degree_types ? array_map('trim', explode(',', $request->degree_types)) : [];

        AcademiaMedicalResidency::create([
            'program_name' => $request->program_name,
            'state_id' => $request->state_id,
            'location' => $request->location,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'degree_types' => $degrees,
            'website' => $request->website,
            'phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'New Residency Program added successfully!');
    }

    public function updateResidency(Request $request, $id)
    {
        $request->validate([
            'program_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'phone' => 'nullable|string|max:255',
        ]);

        $item = AcademiaMedicalResidency::findOrFail($id);

        $degrees = $request->degree_types ? array_map('trim', explode(',', $request->degree_types)) : [];

        $item->update([
            'program_name' => $request->program_name,
            'state_id' => $request->state_id,
            'location' => $request->location,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'degree_types' => $degrees,
            'website' => $request->website,
            'phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Residency Program updated successfully!');
    }

    public function destroyResidency($id)
    {
        $residency = AcademiaMedicalResidency::findOrFail($id);
        $residency->delete();

        return redirect()->back()->with('success', 'Medical Residency program deleted successfully!');
    }

    // Jobs List
    public function indexJobs(Request $request)
    {
        $query = AcademiaJob::with('state');

        // Search by Title or Company Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                    ->orWhere('company_name', 'LIKE', "%$search%");
            });
        }

        // Filter by State
        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        // Filter by Category (state_institution / private_practice)
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $data = $query->latest()->paginate(20)->withQueryString();
        $states = State::orderBy('name')->get();

        return view('admin.academia.employment.index', compact('data', 'states'));
    }

    // Store New Job
    public function storeJob(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'category' => 'required|in:state_institution,private_practice',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
        ]);

        AcademiaJob::create([
            'state_id' => $request->state_id,
            'title' => $request->title,
            'company_name' => $request->company_name,
            'location' => $request->location,
            'salary_min' => $request->salary_min,
            'salary_max' => $request->salary_max,
            'category' => $request->category,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'employment_type' => $request->employment_type,
            'work_mode' => $request->work_mode,
        ]);

        return redirect()->back()->with('success', 'New Job Opening added successfully!');
    }

    public function updateJob(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'category' => 'required|in:state_institution,private_practice',
        ]);

        $job = AcademiaJob::findOrFail($id);

        $job->update([
            'state_id' => $request->state_id,
            'title' => $request->title,
            'company_name' => $request->company_name,
            'location' => $request->location,
            'salary_min' => $request->salary_min,
            'salary_max' => $request->salary_max,
            'category' => $request->category,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'employment_type' => $request->employment_type,
            'work_mode' => $request->work_mode,
        ]);

        return redirect()->back()->with('success', 'Job information updated successfully!');
    }

    public function destroyJob($id)
    {
        $job = AcademiaJob::findOrFail($id);
        $job->delete();

        return redirect()->back()->with('success', 'Job deleted successfully!');
    }
}
