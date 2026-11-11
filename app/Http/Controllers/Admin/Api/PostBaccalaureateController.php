<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderPostBaccalaureateCategoriesRequest;
use App\Http\Requests\Admin\StorePostBaccalaureateCategoryRequest;
use App\Http\Requests\Admin\StorePostBaccalaureateProgramRequest;
use App\Http\Requests\Admin\UpdatePostBaccalaureateCategoryRequest;
use App\Http\Requests\Admin\UpdatePostBaccalaureateProgramRequest;
use App\Http\Resources\PostBaccalaureateCategoryResource;
use App\Http\Resources\PostBaccalaureateProgramResource;
use App\Models\AcademiaPostBaccalaureateCategory;
use App\Models\AcademiaPostBaccalaureateProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostBaccalaureateController extends Controller
{
    public function indexCategories(Request $request): JsonResponse
    {
        $query = AcademiaPostBaccalaureateCategory::query()
            ->withCount('programs')
            ->ordered();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate categories retrieved successfully.',
            'data' => PostBaccalaureateCategoryResource::collection($categories)->resolve($request),
        ]);
    }

    public function storeCategory(StorePostBaccalaureateCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = DB::transaction(function () use ($validated): AcademiaPostBaccalaureateCategory {
            $nextSortOrder = ((int) AcademiaPostBaccalaureateCategory::query()->max('sort_order')) + 1;

            return AcademiaPostBaccalaureateCategory::query()->create([
                'name' => $validated['name'],
                'sort_order' => $nextSortOrder,
                'is_active' => $validated['is_active'] ?? true,
            ]);
        });

        $category->loadCount('programs');

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate category added successfully.',
            'data' => (new PostBaccalaureateCategoryResource($category))->resolve($request),
        ], 201);
    }

    public function updateCategory(UpdatePostBaccalaureateCategoryRequest $request, int $id): JsonResponse
    {
        $category = AcademiaPostBaccalaureateCategory::query()->find($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Post-baccalaureate category not found.',
            ], 404);
        }

        $category->update($request->validated());
        $category->loadCount('programs');

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate category updated successfully.',
            'data' => (new PostBaccalaureateCategoryResource($category))->resolve($request),
        ]);
    }

    public function reorderCategories(ReorderPostBaccalaureateCategoriesRequest $request): JsonResponse
    {
        $orderedIds = array_map('intval', $request->validated('category_ids'));

        DB::transaction(function () use ($orderedIds): void {
            $allIds = AcademiaPostBaccalaureateCategory::query()
                ->ordered()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $remainingIds = array_values(array_diff($allIds, $orderedIds));
            $finalOrder = array_values(array_merge($orderedIds, $remainingIds));

            foreach ($finalOrder as $index => $categoryId) {
                AcademiaPostBaccalaureateCategory::query()
                    ->where('id', $categoryId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        $categories = AcademiaPostBaccalaureateCategory::query()
            ->withCount('programs')
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate categories reordered successfully.',
            'data' => PostBaccalaureateCategoryResource::collection($categories)->resolve($request),
        ]);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $category = AcademiaPostBaccalaureateCategory::query()
            ->withCount('programs')
            ->find($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Post-baccalaureate category not found.',
            ], 404);
        }

        if ($category->programs_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete '{$category->name}' because it is assigned to {$category->programs_count} program(s). Deactivate it or unassign those programs first.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate category deleted successfully.',
        ]);
    }

    public function indexPrograms(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 20), 100));

        $query = AcademiaPostBaccalaureateProgram::query()
            ->with(['state', 'categories'])
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search): void {
                $q->where('program_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($catQuery) use ($search): void {
                        $catQuery->where('academia_post_baccalaureate_categories.name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->integer('state_id'));
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->integer('category_id');

            $query->whereHas('categories', function ($q) use ($categoryId): void {
                $q->where('academia_post_baccalaureate_categories.id', $categoryId);
            });
        }

        $programs = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Post-baccalaureate programs fetched successfully.',
            'data' => PostBaccalaureateProgramResource::collection($programs->items())->resolve($request),
            'pagination' => [
                'current_page' => $programs->currentPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'last_page' => $programs->lastPage(),
                'from' => $programs->firstItem(),
                'to' => $programs->lastItem(),
                'has_more_pages' => $programs->hasMorePages(),
                'next_page_url' => $programs->nextPageUrl(),
                'prev_page_url' => $programs->previousPageUrl(),
            ],
        ]);
    }

    public function storeProgram(StorePostBaccalaureateProgramRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $program = DB::transaction(function () use ($validated): AcademiaPostBaccalaureateProgram {
            $program = AcademiaPostBaccalaureateProgram::query()->create([
                'state_id' => $validated['state_id'],
                'program_name' => $validated['program_name'],
                'location' => $validated['location'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'latitude' => isset($validated['latitude']) ? (string) $validated['latitude'] : null,
                'longitude' => isset($validated['longitude']) ? (string) $validated['longitude'] : null,
            ]);

            $program->categories()->sync($validated['category_ids']);

            return $program;
        });

        $program->load(['state', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'New Post-Baccalaureate Program added successfully.',
            'data' => (new PostBaccalaureateProgramResource($program))->resolve($request),
        ], 201);
    }

    public function updateProgram(UpdatePostBaccalaureateProgramRequest $request, int $id): JsonResponse
    {
        $program = AcademiaPostBaccalaureateProgram::query()->find($id);

        if (! $program) {
            return response()->json([
                'success' => false,
                'message' => 'Post-baccalaureate program not found.',
            ], 404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($program, $validated): void {
            $attributes = collect($validated)
                ->except(['category_ids'])
                ->all();

            if (array_key_exists('latitude', $attributes) && $attributes['latitude'] !== null) {
                $attributes['latitude'] = (string) $attributes['latitude'];
            }

            if (array_key_exists('longitude', $attributes) && $attributes['longitude'] !== null) {
                $attributes['longitude'] = (string) $attributes['longitude'];
            }

            if ($attributes !== []) {
                $program->update($attributes);
            }

            if (array_key_exists('category_ids', $validated)) {
                $program->categories()->sync($validated['category_ids']);
            }
        });

        $program->load(['state', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'Post-Baccalaureate Program updated successfully.',
            'data' => (new PostBaccalaureateProgramResource($program))->resolve($request),
        ]);
    }

    public function destroyProgram(int $id): JsonResponse
    {
        $program = AcademiaPostBaccalaureateProgram::query()->find($id);

        if (! $program) {
            return response()->json([
                'success' => false,
                'message' => 'Post-baccalaureate program not found.',
            ], 404);
        }

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post-Baccalaureate Program deleted successfully.',
        ]);
    }
}
