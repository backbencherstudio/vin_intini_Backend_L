<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryItem;
use App\Models\IndustrySections;
use Illuminate\Http\Request;

class IndustryPublicationController extends Controller
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
        $query = IndustryItem::whereHas('IndustryCategory.IndustrySection', function ($q) use ($network) {
            $q->where('industry_type', 'publications')->where('network_type', $network);
        });

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $items = $query->with('IndustryCategory.IndustrySection')->latest()->paginate(30)->withQueryString();

        return view('admin.industry.publication', compact('items', 'network'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'network_type' => 'required',
        ]);

        $section = IndustrySections::firstOrCreate(
            ['industry_type' => 'publications', 'network_type' => $request->network_type, 'name' => 'Newly Published']
        );

        $category = IndustryCategory::firstOrCreate(
            ['section_id' => $section->id, 'category_name' => 'All']
        );

        $data = $request->only(['title', 'tag', 'pub_date', 'extra_tag', 'description', 'link']);
        $data['category_id'] = $category->id;

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);

        return back()->with('success', 'Publication article saved successfully!');
    }

    public function destroy($id)
    {
        IndustryItem::findOrFail($id)->delete();

        return back()->with('success', 'Article deleted successfully!');
    }
}
