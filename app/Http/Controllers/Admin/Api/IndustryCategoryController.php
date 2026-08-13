<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\IndustrySections;
use Illuminate\Http\Request;

class IndustryCategoryController extends Controller
{
    public function psychology(Request $request)
    {
        $query = IndustrySections::where('network_type', 'psychology')
            ->where('industry_type', '!=', 'publications')
            ->with([
                'IndustryCategory' => function ($query) {
                    $query->select(
                        'id',
                        'section_id',
                        'category_name'
                    );
                }
            ])
            ->latest();

        if ($request->filled('type')) {
            $query->where('industry_type', $request->type);
        }

        $sections = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Psychology sections fetched successfully.',
            'data' => [
                'network' => 'psychology',
                'sections' => $sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'industry_type' => $section->industry_type,
                        'name' => $section->name,
                        'network_type' => $section->network_type,

                        'categories' => $section->IndustryCategory->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'section_id' => $category->section_id,
                                'category_name' => $category->category_name,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }
}
