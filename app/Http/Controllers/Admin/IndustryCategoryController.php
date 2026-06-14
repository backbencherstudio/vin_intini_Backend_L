<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use Illuminate\Http\Request;

class IndustryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryCategory::query();

        if ($request->filled('search')) {
            $query->where('category_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('network')) {
            $query->where('network_type', $request->network);
        }
        if ($request->filled('industry')) {
            $query->where('industry_type', $request->industry);
        }

        $categories = $query->latest()->paginate(20)->withQueryString();

       $uniqueSections = IndustryCategory::whereNotNull('section_name')
                  ->where('section_name', '!=', '')
                  ->distinct()
                  ->pluck('section_name');

        return view('admin.industry.category', compact('categories', 'uniqueSections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'network_type' => 'required',
            'industry_type' => 'required',
            'section_name' => 'required',
            'category_name' => 'nullable',
        ]);

        IndustryCategory::updateOrCreate(
            ['id' => $request->category_id],
            $request->only(['network_type', 'industry_type', 'section_name', 'category_name'])
        );

        return back()->with('success', 'Category saved successfully!');
    }

    public function destroy($id)
    {
        IndustryCategory::findOrFail($id)->delete();
        return back()->with('success', 'Category deleted successfully!');
    }
}
