<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryItem;
use App\Models\IndustrySections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IndustryPharmaController extends Controller
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
            $q->where('industry_type', 'psychotropics')->where('network_type', $network);
        });

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $items = $query->with('IndustryCategory.IndustrySection')->latest()->paginate(30)->withQueryString();

        $sections = IndustrySections::where('industry_type', 'psychotropics')
            ->where('network_type', $network)
            ->with('IndustryCategory') 
            ->get();

        return view('admin.industry.pharma', compact('items', 'sections', 'network'));
    }

    public function store(Request $request)
    {
        $request->validate(['category_id' => 'required', 'title' => 'required']);
        $data = $request->only(['category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link']);

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);
        return back()->with('success', 'Information updated successfully!');
    }

    public function destroy($id)
    {
        IndustryItem::findOrFail($id)->delete();
        return back()->with('success', 'Deleted successfully!');
    }

    // public function psychology(Request $request)
    // {
    //     return $this->renderView($request, 'psychology');
    // }
    // public function neuroscience(Request $request)
    // {
    //     return $this->renderView($request, 'neuroscience');
    // }

    // private function renderView($request, $network)
    // {
    //     $query = IndustryItem::whereHas('IndustryCategory.IndustrySection', function ($q) use ($network) {
    //         $q->where('industry_type', 'psychotropics')->where('network_type', $network);
    //     });

    //     if ($request->filled('search')) {
    //         $query->where('title', 'like', '%' . $request->search . '%');
    //     }

    //     $items = $query->with('IndustryCategory.IndustrySection')->latest()->paginate(30)->withQueryString();

    //     $sections = IndustrySections::where('industry_type', 'psychotropics')
    //         ->where('network_type', $network)
    //         ->with('IndustryCategory')
    //         ->get();

    //     return view('admin.industry.pharma', compact('items', 'sections', 'network'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate(['category_id' => 'required', 'title' => 'required']);
    //     $data = $request->only(['category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link']);

    //     if ($request->hasFile('image')) {
    //         if ($request->item_id) {
    //             $oldItem = IndustryItem::find($request->item_id);
    //             if ($oldItem && $oldItem->image) {
    //                 Storage::disk('public')->delete($oldItem->image);
    //             }
    //         }
    //         $data['image'] = $request->file('image')->store('industry', 'public');
    //     }

    //     IndustryItem::updateOrCreate(['id' => $request->item_id], $data);
    //     return back()->with('success', 'Information saved successfully!');
    // }

    // public function destroy($id)
    // {
    //     $item = IndustryItem::findOrFail($id);
    //     if ($item->image) {
    //         Storage::disk('public')->delete($item->image);
    //     }
    //     $item->delete();
    //     return back()->with('success', 'Deleted successfully!');
    // }
}
