<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademiaFacility;
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
            $query->where('name', 'LIKE', '%' . $request->search . '%');
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
        $neuroDegrees = $request->neuroscience_degrees ? array_map('trim', explode(',', $request->neuroscience_degrees)) : [];

        AcademiaUniversity::create([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'has_online_options' => $request->has('has_online_options'),
            'psychology_degrees' => $psychDegrees,
            'neuroscience_degrees' => $neuroDegrees,
        ]);

        return redirect()->back()->with('success', 'New University added successfully!');
    }

    // Update University
    public function updateUniversity(Request $request, $id)
    {
        $uni = AcademiaUniversity::findOrFail($id);

        // String to Array Conversion
        $psych = array_map('trim', explode(',', $request->psychology_degrees));
        $neuro = array_map('trim', explode(',', $request->neuroscience_degrees));

        $uni->update([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'psychology_degrees' => $psych,
            'neuroscience_degrees' => $neuro,
            'has_online_options' => $request->has('has_online_options')
        ]);

        return redirect()->route('admin.universities.index')->with('success', 'University data updated successfully!');
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
        ]);

        return redirect()->back()->with('success', 'Facility updated successfully!');
    }

    public function storeFacility(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'type' => 'required',
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
        ]);

        $degrees = $request->degree_types ? array_map('trim', explode(',', $request->degree_types)) : [];

        AcademiaMedicalResidency::create([
            'program_name' => $request->program_name,
            'state_id' => $request->state_id,
            'location' => $request->location,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'degree_types' => $degrees,
        ]);

        return redirect()->back()->with('success', 'New Residency Program added successfully!');
    }

    public function updateResidency(Request $request, $id)
    {
        $request->validate([
            'program_name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
        ]);

        $item = AcademiaMedicalResidency::findOrFail($id);

        $degrees = $request->degree_types ? array_map('trim', explode(',', $request->degree_types)) : [];

        $item->update([
            'program_name' => $request->program_name,
            'state_id' => $request->state_id,
            'location' => $request->location,
            'degree_types' => $degrees,
        ]);

        return redirect()->back()->with('success', 'Residency Program updated successfully!');
    }

    public function destroyResidency($id)
    {
        $residency = AcademiaMedicalResidency::findOrFail($id);
        $residency->delete();

        return redirect()->back()->with('success', 'Medical Residency program deleted successfully!');
    }
}
