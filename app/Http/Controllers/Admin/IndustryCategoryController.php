<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustrySections;
use Illuminate\Http\Request;

class IndustryCategoryController extends Controller
{
    public function psychology(Request $request)
    {
        return $this->renderView($request, 'psychology');
    }

    public function neuroscience(Request $request)
    {
        return $this->renderView($request, 'neuroscience');
    }

    private function renderView($request, $network)
    {
        $sections = IndustrySections::where('network_type', $network)
            ->with('IndustryCategory')
            ->latest()
            ->get();

        return view('admin.industry.category', compact('sections', 'network'));
    }

    public function storeSection(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'industry_type' => 'required',
            'network_type' => 'required',
        ]);

        $section = IndustrySections::updateOrCreate(
            ['id' => $request->id],
            $request->except('id')
        );

        IndustryCategory::firstOrCreate(
            ['section_id' => $section->id, 'category_name' => 'All']
        );

        return back()->with('success', 'Section structure updated successfully!');
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'section_id'    => 'required',
        ]);

        IndustryCategory::updateOrCreate(
            ['id' => $request->id],
            $request->except('id')
        );

        return back()->with('success', 'Category Tab saved successfully!');
    }

    public function destroySection($id)
    {
        $section = IndustrySections::findOrFail($id);
        $section->delete();
        return back()->with('success', 'Section and associated data removed.');
    }

    public function destroyCategory($id)
    {
        $category = IndustryCategory::findOrFail($id);

        if ($category->category_name == 'All') {
            return back()->with('error', 'Default category cannot be deleted.');
        }

        $category->delete();
        return back()->with('success', 'Category Tab removed.');
    }
}
