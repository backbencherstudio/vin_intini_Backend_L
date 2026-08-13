<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustrySections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                        // 'network_type' => $section->network_type,

                        'categories' => $section->IndustryCategory->map(function ($category) {
                            return [
                                // 'id' => $category->id,
                                // 'section_id' => $category->section_id,
                                'category_name' => $category->category_name,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }


    public function neuroscience(Request $request)
    {
        $query = IndustrySections::where('network_type', 'neuroscience')
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
            'message' => 'Neuroscience sections fetched successfully.',
            'data' => [
                'network' => 'neuroscience',
                'sections' => $sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'industry_type' => $section->industry_type,
                        'name' => $section->name,

                        'categories' => $section->IndustryCategory->map(function ($category) {
                            return [
                                'category_name' => $category->category_name,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }


    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry_type' => 'required|in:biotechnology,psychotropics',
        ]);

        $section = DB::transaction(function () use ($validated) {

            $section = IndustrySections::create([
                'name' => $validated['name'],
                'industry_type' => $validated['industry_type'],
                'network_type' => 'psychology',
            ]);

            IndustryCategory::firstOrCreate([
                'section_id' => $section->id,
                'category_name' => 'All',
            ]);

            return $section;
        });

        $section->load([
            'IndustryCategory' => function ($query) {
                $query->select(
                    'id',
                    'section_id',
                    'category_name'
                );
            }
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'categories' => $section->IndustryCategory->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'section_id' => $category->section_id,
                        'category_name' => $category->category_name,
                    ];
                })->values(),
            ],
        ], 201);
    }
}
