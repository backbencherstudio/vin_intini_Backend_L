<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryPartner::query();

        if ($request->filled('search')) {
            $query->where('partner_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('network')) {
            $query->where('network_type', $request->network);
        }

        if ($request->filled('industry')) {
            $query->where('industry_type', $request->industry);
        }

        $partners = $query->latest()->paginate(20)->withQueryString();

        return view('admin.industry.partner', compact('partners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_name' => 'required',
            'network_type' => 'required',
            'industry_type' => 'required',
            'partner_logo' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $data = [
            'network_type' => $request->network_type,
            'industry_type' => $request->industry_type,
            'partner_name' => $request->partner_name,
            'partner_tag' => $request->partner_tag,
            'partner_desc' => $request->partner_desc,
            'partner_link' => $request->partner_link,
        ];

        if ($request->hasFile('partner_logo')) {
            if ($request->partner_id) {
                $oldPartner = IndustryPartner::find($request->partner_id);
                if ($oldPartner && $oldPartner->partner_logo) {
                    Storage::disk('public')->delete($oldPartner->partner_logo);
                }
            }
            $path = $request->file('partner_logo')->store('partners', 'public');
            $data['partner_logo'] = $path;
        }

        IndustryPartner::updateOrCreate(['id' => $request->partner_id], $data);

        return back()->with('success', 'Partner data saved successfully!');
    }

    public function destroy($id)
    {
        $partner = IndustryPartner::findOrFail($id);
        if ($partner->partner_logo) {
            Storage::disk('public')->delete($partner->partner_logo);
        }
        $partner->delete();

        return back()->with('success', 'Partner removed successfully!');
    }
}
