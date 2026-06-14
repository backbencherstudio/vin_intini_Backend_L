<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryItem;
use Illuminate\Http\Request;

class IndustryPublicationController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryItem::whereHas('IndustryCategory', function ($q) {
            $q->where('industry_type', 'publications');
        });

        if ($request->filled('network')) {
            $query->whereHas('IndustryCategory', function ($q) use ($request) {
                $q->where('network_type', $request->network);
            });
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $items = $query->with('IndustryCategory')->latest()->paginate(20)->withQueryString();

        $categories = IndustryCategory::where('industry_type', 'publications')
            ->get()
            ->unique('section_name');

        return view('admin.industry.publication', compact('items', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'network_type' => 'required'
        ]);

        $defaultCategory = IndustryCategory::firstOrCreate(
            [
                'industry_type' => 'publications',
                'network_type' => $request->network_type, 
                'category_name' => 'Default'
            ],
            [
                'section_name' => 'Newly Published'
            ]
        );

        $data = $request->only(['title', 'tag', 'pub_date', 'extra_tag', 'description', 'link']);
        $data['category_id'] = $defaultCategory->id;

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);

        return back()->with('success', 'Publication saved successfully!');
    }

    public function destroy($id)
    {
        IndustryItem::findOrFail($id)->delete();
        return back()->with('success', 'Publication deleted successfully!');
    }
}
