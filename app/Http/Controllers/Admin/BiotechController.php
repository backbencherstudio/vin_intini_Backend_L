<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryItem;
use Illuminate\Http\Request;

class BiotechController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryItem::whereHas('IndustryCategory', function ($q) {
            $q->where('industry_type', 'biotechnology');
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

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $items = $query->with('IndustryCategory')->latest()->paginate(20)->withQueryString();

        $sections = IndustryCategory::where('industry_type', 'biotechnology')
            ->distinct()
            ->pluck('section_name');

        $catQuery = IndustryCategory::where('industry_type', 'biotechnology');
        if ($request->filled('network')) $catQuery->where('network_type', $request->network);
        if ($request->filled('section')) $catQuery->where('section_name', $request->section);
        $categories = $catQuery->get();

        return view('admin.industry.biotech', compact('items', 'categories', 'sections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'title' => 'required',
        ]);

        $data = $request->only(['category_id', 'title', 'tag', 'sub_title', 'description', 'link']);

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/industry'), $imageName);
            $data['image'] = $imageName;
        }

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);

        return back()->with('success', 'Biotechnology item saved successfully!');
    }

    public function destroy($id)
    {
        $item = IndustryItem::findOrFail($id);
        if ($item->image && file_exists(public_path('uploads/industry/' . $item->image))) {
            unlink(public_path('uploads/industry/' . $item->image));
        }
        $item->delete();
        return back()->with('success', 'Item deleted successfully!');
    }
}
