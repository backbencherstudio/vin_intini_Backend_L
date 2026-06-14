<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryItem;
use Illuminate\Http\Request;

class IndustryPharmaController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryItem::whereHas('IndustryCategory', function ($q) {
            $q->where('industry_type', 'psychopharmacology');
        });

        if ($request->filled('network')) {
            $query->whereHas('IndustryCategory', function ($q) use ($request) {
                $q->where('network_type', $request->network);
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('IndustryCategory', function ($q) use ($request) {
                $q->where('section_name', $request->section);
            });
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $items = $query->with('IndustryCategory')->latest()->paginate(20)->withQueryString();

        $sections = IndustryCategory::where('industry_type', 'psychopharmacology')
            ->distinct()
            ->pluck('section_name');

        $categories = IndustryCategory::where('industry_type', 'psychopharmacology')
            ->get()
            ->unique('section_name');

        return view('admin.industry.pharma', compact('items', 'categories', 'sections'));
    }

    public function store(Request $request)
    {
        $request->validate(['category_id' => 'required', 'title' => 'required']);

        $data = $request->only(['category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link']);

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/industry'), $imageName);
            $data['image'] = $imageName;
        }

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);

        return back()->with('success', 'Psychopharmacology item saved successfully!');
    }

    public function destroy($id)
    {
        IndustryItem::findOrFail($id)->delete();
        return back()->with('success', 'Item deleted successfully!');
    }
}
