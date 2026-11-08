<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryItem;
use App\Models\IndustrySections;
use App\Services\OptimizedImageUploadService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BiotechController extends Controller
{
    public function psychology(Request $request): Factory|View
    {
        return $this->renderView($request, 'psychology');
    }

    public function neuroscience(Request $request): Factory|View
    {
        return $this->renderView($request, 'neuroscience');
    }

    private function renderView(Request $request, string $network): Factory|View
    {
        $query = IndustryItem::whereHas('IndustryCategory.IndustrySection', function ($q) use ($network): void {
            $q->where('industry_type', 'biotechnology')->where('network_type', $network);
        });

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $items = $query->with('IndustryCategory.IndustrySection')->latest()->paginate(30)->withQueryString();

        $sections = IndustrySections::where('industry_type', 'biotechnology')
            ->where('network_type', $network)
            ->with('IndustryCategory')
            ->get();

        return view('admin.industry.biotech', ['items' => $items, 'sections' => $sections, 'network' => $network]);
    }

    public function store(Request $request, OptimizedImageUploadService $imageUploadService)
    {
        $request->validate(['category_id' => 'required', 'title' => 'required']);
        $data = $request->only(['category_id', 'title', 'tag', 'sub_title', 'description', 'link']);

        if ($request->hasFile('image')) {
            if ($request->item_id) {
                $oldItem = IndustryItem::find($request->item_id);
                if ($oldItem && $oldItem->image) {
                    Storage::disk('public')->delete($oldItem->image);
                }
            }
            $data['image'] = $imageUploadService->store($request->file('image'), 'industry');
        }

        IndustryItem::updateOrCreate(['id' => $request->item_id], $data);

        return back()->with('success', 'Operation successful!');
    }

    public function destroy($id)
    {
        $item = IndustryItem::findOrFail($id);
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }
        $item->delete();

        return back()->with('success', 'Item deleted successfully!');
    }
}
