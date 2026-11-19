<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndustryCategoryController extends Controller
{
    public function psychology(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:biotechnology,psychotropics',
            'search' => 'nullable|string|max:100',
        ]);

        $search = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 12), 1), 100);

        // Overall summary KPI metrics for psychology
        $summary = [
            'total_sections' => IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', '!=', 'publications')->count(),
            'biotechnology_sections' => IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'biotechnology')->count(),
            'psychotropics_sections' => IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'psychotropics')->count(),
            'total_subcategories' => IndustryCategory::whereHas('IndustrySection', function ($q): void {
                $q->where('network_type', 'psychology')
                    ->where('industry_type', '!=', 'publications');
            })->count(),
        ];

        $query = IndustrySections::where('network_type', 'psychology')
            ->where('industry_type', '!=', 'publications')
            ->withCount('products')
            ->with([
                'IndustryCategory' => function ($query): void {
                    $query->select(
                        'id',
                        'section_id',
                        'category_name',
                        'is_active',
                        'sort_order'
                    )->withCount('products')->ordered();
                },
            ])
            ->ordered();

        if ($request->filled('type')) {
            $query->where('industry_type', $request->type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('IndustryCategory', function ($cq) use ($search): void {
                        $cq->where('category_name', 'like', "%{$search}%");
                    });
            });
        }

        $sections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Psychology sections fetched successfully.',
            'summary' => $summary,
            'data' => [
                'network' => 'psychology',
                'summary' => $summary,
                'sections' => $sections->getCollection()->map(fn ($section): array => [
                    'id' => $section->id,
                    'network_type' => $section->network_type,
                    'industry_type' => $section->industry_type,
                    'name' => $section->name,
                    'is_active' => (bool) ($section->is_active ?? true),
                    'sort_order' => (int) ($section->sort_order ?? 0),
                    'products_count' => (int) ($section->products_count ?? 0),
                    'categories_count' => $section->IndustryCategory->count(),
                    'categories' => $section->IndustryCategory->map(fn ($category): array => [
                        'id' => $category->id,
                        'section_id' => $category->section_id,
                        'category_name' => $category->category_name,
                        'is_active' => (bool) ($category->is_active ?? true),
                        'sort_order' => (int) ($category->sort_order ?? 0),
                        'products_count' => (int) ($category->products_count ?? 0),
                    ])->values(),
                ])->values(),
                'pagination' => [
                    'current_page' => $sections->currentPage(),
                    'per_page' => $sections->perPage(),
                    'total' => $sections->total(),
                    'last_page' => $sections->lastPage(),
                    'has_more_pages' => $sections->hasMorePages(),
                ],
            ],
        ]);
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry_type' => 'required|in:biotechnology,psychotropics',
        ]);

        $maxOrder = IndustrySections::where('network_type', 'psychology')
            ->where('industry_type', $validated['industry_type'])
            ->max('sort_order') ?? 0;

        $section = IndustrySections::create([
            'name' => $validated['name'],
            'industry_type' => $validated['industry_type'],
            'network_type' => 'psychology',
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        $section->load([
            'IndustryCategory' => function ($query): void {
                $query->select(
                    'id',
                    'section_id',
                    'category_name',
                    'is_active',
                    'sort_order'
                )->ordered();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'is_active' => (bool) $section->is_active,
                'sort_order' => (int) $section->sort_order,
                'products_count' => 0,
                'categories_count' => 0,
                'categories' => [],
            ],
        ], 201);
    }

    public function updateSection(Request $request, $id)
    {
        $section = IndustrySections::where('network_type', 'psychology')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'industry_type' => 'sometimes|required|in:biotechnology,psychotropics',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['industry_type'])) {
            $updateData['industry_type'] = $validated['industry_type'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = (bool) $validated['is_active'];
        }

        $section->update($updateData);

        $section->load([
            'IndustryCategory' => function ($query): void {
                $query->select(
                    'id',
                    'section_id',
                    'category_name',
                    'is_active',
                    'sort_order'
                )->ordered();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully.',
            'data' => [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'is_active' => (bool) $section->is_active,
                'sort_order' => (int) $section->sort_order,
                'categories' => $section->IndustryCategory->map(fn ($category): array => [
                    'id' => $category->id,
                    'section_id' => $category->section_id,
                    'category_name' => $category->category_name,
                    'is_active' => (bool) ($category->is_active ?? true),
                    'sort_order' => (int) ($category->sort_order ?? 0),
                ])->values(),
            ],
        ]);
    }

    public function neuroscience(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:biotechnology,psychotropics',
            'search' => 'nullable|string|max:100',
        ]);

        $search = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 12), 1), 100);

        // Overall summary KPI metrics for neuroscience
        $summary = [
            'total_sections' => IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', '!=', 'publications')->count(),
            'biotechnology_sections' => IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'biotechnology')->count(),
            'psychotropics_sections' => IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychotropics')->count(),
            'total_subcategories' => IndustryCategory::whereHas('IndustrySection', function ($q): void {
                $q->where('network_type', 'neuroscience')
                    ->where('industry_type', '!=', 'publications');
            })->count(),
        ];

        $query = IndustrySections::where('network_type', 'neuroscience')
            ->where('industry_type', '!=', 'publications')
            ->withCount('products')
            ->with([
                'IndustryCategory' => function ($query): void {
                    $query->select(
                        'id',
                        'section_id',
                        'category_name',
                        'is_active',
                        'sort_order'
                    )->withCount('products')->ordered();
                },
            ])
            ->ordered();

        if ($request->filled('type')) {
            $query->where('industry_type', $request->type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('IndustryCategory', function ($cq) use ($search): void {
                        $cq->where('category_name', 'like', "%{$search}%");
                    });
            });
        }

        $sections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Neuroscience sections fetched successfully.',
            'summary' => $summary,
            'data' => [
                'network' => 'neuroscience',
                'summary' => $summary,
                'sections' => $sections->getCollection()->map(fn ($section): array => [
                    'id' => $section->id,
                    'network_type' => $section->network_type,
                    'industry_type' => $section->industry_type,
                    'name' => $section->name,
                    'is_active' => (bool) ($section->is_active ?? true),
                    'sort_order' => (int) ($section->sort_order ?? 0),
                    'products_count' => (int) ($section->products_count ?? 0),
                    'categories_count' => $section->IndustryCategory->count(),
                    'categories' => $section->IndustryCategory->map(fn ($category): array => [
                        'id' => $category->id,
                        'section_id' => $category->section_id,
                        'category_name' => $category->category_name,
                        'is_active' => (bool) ($category->is_active ?? true),
                        'sort_order' => (int) ($category->sort_order ?? 0),
                        'products_count' => (int) ($category->products_count ?? 0),
                    ])->values(),
                ])->values(),
                'pagination' => [
                    'current_page' => $sections->currentPage(),
                    'per_page' => $sections->perPage(),
                    'total' => $sections->total(),
                    'last_page' => $sections->lastPage(),
                    'has_more_pages' => $sections->hasMorePages(),
                ],
            ],
        ]);
    }

    public function storeNeuroSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry_type' => 'required|in:biotechnology,psychotropics',
        ]);

        $maxOrder = IndustrySections::where('network_type', 'neuroscience')
            ->where('industry_type', $validated['industry_type'])
            ->max('sort_order') ?? 0;

        $section = IndustrySections::create([
            'name' => $validated['name'],
            'industry_type' => $validated['industry_type'],
            'network_type' => 'neuroscience',
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        $section->load([
            'IndustryCategory' => function ($query): void {
                $query->select(
                    'id',
                    'section_id',
                    'category_name',
                    'is_active',
                    'sort_order'
                )->ordered();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'is_active' => (bool) $section->is_active,
                'sort_order' => (int) $section->sort_order,
                'products_count' => 0,
                'categories_count' => 0,
                'categories' => [],
            ],
        ], 201);
    }

    public function updateNeuroSection(Request $request, $id)
    {
        $section = IndustrySections::where('network_type', 'neuroscience')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'industry_type' => 'sometimes|required|in:biotechnology,psychotropics',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['industry_type'])) {
            $updateData['industry_type'] = $validated['industry_type'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = (bool) $validated['is_active'];
        }

        $section->update($updateData);

        $section->load([
            'IndustryCategory' => function ($query): void {
                $query->select(
                    'id',
                    'section_id',
                    'category_name',
                    'is_active',
                    'sort_order'
                )->ordered();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully.',
            'data' => [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'is_active' => (bool) $section->is_active,
                'sort_order' => (int) $section->sort_order,
                'categories' => $section->IndustryCategory->map(fn ($category): array => [
                    'id' => $category->id,
                    'section_id' => $category->section_id,
                    'category_name' => $category->category_name,
                    'is_active' => (bool) ($category->is_active ?? true),
                    'sort_order' => (int) ($category->sort_order ?? 0),
                ])->values(),
            ],
        ]);
    }

    public function reorderSections(Request $request)
    {
        $validated = $request->validate([
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'integer|exists:industry_sections,id',
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['section_ids'] as $index => $id) {
                IndustrySections::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sections reordered successfully.',
        ]);
    }

    public function toggleSectionStatus(Request $request, $id)
    {
        $section = IndustrySections::findOrFail($id);
        $isActive = $request->has('is_active') ? (bool) $request->is_active : !$section->is_active;

        $section->update([
            'is_active' => $isActive,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Section '{$section->name}' status updated to " . ($isActive ? 'Active' : 'Hidden') . '.',
            'data' => [
                'id' => $section->id,
                'is_active' => (bool) $section->is_active,
            ],
        ]);
    }

    public function destroySection($id)
    {
        $section = IndustrySections::findOrFail($id);

        $linkedProductsCount = IndustryProduct::where('section_id', $section->id)->count();
        if ($linkedProductsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete section '{$section->name}'. It currently has {$linkedProductsCount} active product(s) linked to it. Please reassign or delete the products first.",
            ], 422);
        }

        $sectionName = $section->name;
        // Cleanly delete all child categories before removing section
        $section->IndustryCategory()->delete();
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => "Section '{$sectionName}' removed successfully.",
        ]);
    }

    public function storeSubCategory(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'section_id' => 'required|exists:industry_sections,id',
        ]);

        $categoryName = trim($validated['category_name']);
        $sectionId = (int) $validated['section_id'];

        $exists = IndustryCategory::where('section_id', $sectionId)
            ->where('category_name', $categoryName)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Subcategory '{$categoryName}' already exists in this section.",
            ], 422);
        }

        $maxOrder = (int) IndustryCategory::where('section_id', $sectionId)->max('sort_order');

        $category = IndustryCategory::create([
            'section_id' => $sectionId,
            'category_name' => $categoryName,
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subcategory '{$categoryName}' created successfully.",
            'data' => [
                'id' => $category->id,
                'section_id' => $category->section_id,
                'category_name' => $category->category_name,
                'is_active' => true,
                'sort_order' => $category->sort_order,
                'products_count' => 0,
            ],
        ], 201);
    }

    public function updateSubCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'category_name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = IndustryCategory::findOrFail($id);

        if (isset($validated['category_name'])) {
            $categoryName = trim($validated['category_name']);
            $exists = IndustryCategory::where('section_id', $category->section_id)
                ->where('category_name', $categoryName)
                ->where('id', '!=', $category->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => "Another subcategory named '{$categoryName}' already exists in this section.",
                ], 422);
            }

            $category->category_name = $categoryName;
        }

        if (isset($validated['is_active'])) {
            $category->is_active = (bool) $validated['is_active'];
        }

        $category->save();

        return response()->json([
            'success' => true,
            'message' => "Subcategory updated successfully.",
            'data' => [
                'id' => $category->id,
                'section_id' => $category->section_id,
                'category_name' => $category->category_name,
                'is_active' => (bool) $category->is_active,
                'sort_order' => (int) $category->sort_order,
            ],
        ]);
    }

    public function reorderSubcategories(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:industry_categories,id',
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['category_ids'] as $index => $id) {
                IndustryCategory::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Subcategories reordered successfully.',
        ]);
    }

    public function toggleSubcategoryStatus(Request $request, $id)
    {
        $category = IndustryCategory::findOrFail($id);
        $isActive = $request->has('is_active') ? (bool) $request->is_active : !$category->is_active;

        $category->update([
            'is_active' => $isActive,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subcategory '{$category->category_name}' status updated to " . ($isActive ? 'Active' : 'Hidden') . '.',
            'data' => [
                'id' => $category->id,
                'is_active' => (bool) $category->is_active,
            ],
        ]);
    }

    public function destroySubCategory($id)
    {
        $category = IndustryCategory::findOrFail($id);

        $linkedProductsCount = IndustryProduct::where('category_id', $category->id)->count();
        if ($linkedProductsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete subcategory '{$category->category_name}'. It currently has {$linkedProductsCount} product(s) linked to it. Please reassign or delete the products first.",
            ], 422);
        }

        $categoryName = $category->category_name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Subcategory '{$categoryName}' removed successfully.",
        ]);
    }
}
