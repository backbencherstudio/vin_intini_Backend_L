<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminProductRequest;
use App\Http\Requests\Admin\UpdateAdminProductRequest;
use App\Http\Resources\IndustryProductResource;
use App\Models\Industry;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use App\Services\Industry\IndustryProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductManagementController extends Controller
{
    public function __construct(
        protected IndustryProductService $productService
    ) {}

    /**
     * List verified and active industries for admin product upload selection.
     */
    public function verifiedIndustries(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'industry_type' => ['nullable', 'string', 'max:50'],
        ]);

        $query = Industry::verifiedActive()
            ->with(['creator:id,first_name,last_name,username,email,is_verified,is_suspended'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('industry', 'LIKE', "%{$search}%")
                    ->orWhereHas('creator', function (Builder $creator) use ($search): void {
                        $creator->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('industry_type') && $request->input('industry_type') !== 'all') {
            $query->where('industry', 'LIKE', '%'.trim((string) $request->input('industry_type')).'%');
        }

        $industries = $query->get()->map(function (Industry $industry): array {
            $creator = $industry->creator;
            $ownerName = $creator
                ? trim(($creator->first_name ?? '').' '.($creator->last_name ?? ''))
                : null;
            $ownerName = $ownerName ?: ($creator?->username ?? 'Verified Partner');

            return [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'industry' => $industry->industry,
                'status' => $industry->status ?? 'active',
                'logo_url' => $industry->logo,
                'website' => $industry->website,
                'authorization_confirmed' => (bool) $industry->authorization_confirmed,
                'owner' => $creator ? [
                    'id' => $creator->id,
                    'full_name' => $ownerName,
                    'email' => $creator->email,
                    'is_verified' => (bool) $creator->is_verified,
                ] : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Verified industries retrieved successfully.',
            'data' => $industries,
        ]);
    }

    /**
     * List admin products grouped by section (or paginated flat for a single section).
     */
    public function index(Request $request): JsonResponse
    {
        $this->normalizeScopeInputs($request);

        $request->validate([
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['nullable', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['nullable', 'integer', 'exists:industry_categories,id'],
            'status' => ['nullable', 'string', 'in:all,active,inactive,draft,rejected'],
            'search' => ['nullable', 'string', 'max:100'],
            'limit_per_section' => ['nullable', 'integer', 'min:1', 'max:200'],
            'flat' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $this->expireProductFlags();

        $networkType = (string) $request->input('network_type');
        $industryType = (string) $request->input('industry_type');
        $statusFilter = (string) $request->input('status', 'all');

        $sectionId = $request->filled('section_id') ? $request->integer('section_id') : null;
        $summary = $this->buildSummary($networkType, $industryType, $sectionId);

        // Flat paginated mode (used when expanding/viewing a single section)
        if ($request->boolean('flat') || $sectionId !== null) {
            $sectionMeta = null;
            if ($sectionId !== null) {
                $section = IndustrySections::with([
                    'IndustryCategory' => fn ($q) => $q->select('id', 'section_id', 'category_name')->orderBy('category_name'),
                ])
                    ->where('network_type', $networkType)
                    ->where('industry_type', $industryType)
                    ->find($sectionId);

                if (! $section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Section not found for the selected network and industry.',
                    ], 404);
                }

                $categoryCounts = $this->filteredProductsQuery($request, $networkType, $industryType, $statusFilter, false)
                    ->selectRaw('category_id, count(*) as total')
                    ->groupBy('category_id')
                    ->pluck('total', 'category_id');

                $sectionMeta = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'network_type' => $section->network_type,
                    'industry_type' => $section->industry_type,
                    'total_products' => (int) $categoryCounts->sum(),
                    'categories' => $section->IndustryCategory->map(fn ($cat): array => [
                        'id' => $cat->id,
                        'category_name' => $cat->category_name,
                        'products_count' => (int) $categoryCounts->get($cat->id, 0),
                    ])->values(),
                ];
            }

            $perPage = min(max($request->integer('per_page', 12), 1), 100);
            $baseQuery = $this->filteredProductsQuery($request, $networkType, $industryType, $statusFilter);
            $totalCount = (clone $baseQuery)->count();

            $paginated = $baseQuery
                ->with(['creator', 'industry', 'section', 'category', 'activeCampaign', 'latestCampaign'])
                ->feedOrder()
                ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

            $data = $paginated->getCollection()->map(
                fn (IndustryProduct $product): array => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request)
            )->values();

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully.',
                'summary' => $summary,
                'section' => $sectionMeta,
                'data' => $data,
                'pagination' => [
                    'limit' => $paginated->perPage(),
                    'per_page' => $paginated->perPage(),
                    'next_cursor' => $paginated->nextCursor()?->encode(),
                    'prev_cursor' => $paginated->previousCursor()?->encode(),
                    'has_more_pages' => $paginated->hasMorePages(),
                    'total' => $totalCount,
                ],
            ]);
        }

        // Section-wise grouped mode (default Admin UI)
        $limitPerSection = min(max($request->integer('limit_per_section', 50), 1), 200);

        $sections = IndustrySections::query()
            ->where('network_type', $networkType)
            ->where('industry_type', $industryType)
            ->with([
                'IndustryCategory' => function ($q): void {
                    $q->select('id', 'section_id', 'category_name')->orderBy('category_name');
                },
            ])
            ->orderBy('id')
            ->get();

        $sectionIds = $sections->pluck('id')->all();
        $groupedProducts = collect();
        $sectionCounts = collect();
        $categoryCounts = collect();

        if ($sectionIds !== []) {
            $baseQuery = $this->filteredProductsQuery($request, $networkType, $industryType, $statusFilter)
                ->whereIn('section_id', $sectionIds);

            $sectionCounts = (clone $baseQuery)
                ->groupBy('section_id')
                ->selectRaw('section_id, count(*) as total')
                ->pluck('total', 'section_id');

            $categoryCounts = (clone $baseQuery)
                ->groupBy('category_id')
                ->selectRaw('category_id, count(*) as total')
                ->pluck('total', 'category_id');

            $now = now()->toDateTimeString();
            $rankedSubquery = (clone $baseQuery)
                ->select('industry_products.id')
                ->selectRaw(
                    'ROW_NUMBER() OVER (PARTITION BY section_id ORDER BY CASE WHEN is_boosted = 1 AND (boost_expires_at IS NULL OR boost_expires_at > ?) THEN 2 WHEN is_new_release = 1 AND (new_release_expires_at IS NULL OR new_release_expires_at > ?) THEN 1 ELSE 0 END DESC, boost_score DESC, industry_products.id DESC) as rn',
                    [$now, $now]
                );

            $topProductIds = DB::query()
                ->fromSub($rankedSubquery, 'ranked')
                ->where('rn', '<=', $limitPerSection)
                ->pluck('id')
                ->all();

            if ($topProductIds !== []) {
                $products = IndustryProduct::query()
                    ->whereIn('id', $topProductIds)
                    ->with(['creator', 'industry', 'section', 'category', 'activeCampaign', 'latestCampaign'])
                    ->feedOrder()
                    ->get();

                $groupedProducts = $products->groupBy(fn (IndustryProduct $item): int => (int) $item->section_id);
            }
        }

        $data = $sections->map(function (IndustrySections $section) use ($groupedProducts, $sectionCounts, $categoryCounts, $limitPerSection, $request): array {
            $sectionProducts = $groupedProducts->get((int) $section->id, collect())
                ->map(fn (IndustryProduct $product): array => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request))
                ->values();

            $totalProducts = (int) $sectionCounts->get($section->id, 0);

            return [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'total_products' => $totalProducts,
                'has_more' => $totalProducts > $limitPerSection,
                'categories' => $section->IndustryCategory->map(fn ($category): array => [
                    'id' => $category->id,
                    'category_name' => $category->category_name,
                    'products_count' => (int) $categoryCounts->get($category->id, 0),
                ])->values(),
                'products' => $sectionProducts,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Section-wise products retrieved successfully.',
            'data' => [
                'network_type' => $networkType,
                'industry_type' => $industryType,
                'summary' => $summary,
                'sections' => $data,
            ],
        ]);
    }

    /**
     * Store a new product created by Admin under a verified industry.
     */
    public function store(StoreAdminProductRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = auth('api')->user();
        $validated = $request->validated();
        $industry = Industry::verifiedActive()->findOrFail((int) $validated['industry_id']);

        if ($request->has('information_confirmed')) {
            $validated['information_confirmed'] = $request->boolean('information_confirmed', true);
        }

        $product = $this->productService->createProduct(
            $admin,
            $industry,
            $validated,
            $request->file('image')
        );

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully under verified industry.',
            'data' => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request),
        ], 201);
    }

    /**
     * Show a single product for admin inspection/editing.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $product = $this->findProduct($id, ['creator', 'industry', 'section', 'category', 'activeCampaign', 'latestCampaign']);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request),
        ]);
    }

    /**
     * Update an existing product as Admin.
     */
    public function update(UpdateAdminProductRequest $request, int|string $id): JsonResponse
    {
        $product = $this->findProduct($id);
        $validated = $request->validated();

        if ($request->has('information_confirmed')) {
            $validated['information_confirmed'] = $request->boolean('information_confirmed');
        }

        $product = $this->productService->updateProduct(
            $product,
            $validated,
            $request->file('image')
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request),
        ]);
    }

    /**
     * Toggle or update a product status (`active`, `inactive`, `draft`).
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $product = $this->findProduct($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,draft'],
        ]);

        $product->status = $validated['status'];
        $this->productService->clearRejectionMetadata($product);
        $product->save();

        return response()->json([
            'success' => true,
            'message' => "Product status updated to {$product->status} successfully.",
            'data' => [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'status' => $product->status,
                'rejection_reason' => $product->rejection_reason,
                'rejected_at' => $product->rejected_at?->toISOString(),
                'updated_at' => optional($product->updated_at)?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Reject an illegal or non-compliant product with a mandatory reason,
     * hide it platform-wide, and notify the industry owner.
     */
    public function reject(Request $request, int|string $id): JsonResponse
    {
        $product = $this->findProduct($id, ['creator', 'industry.creator']);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'Please provide a reason for rejecting this product.',
            'reason.min' => 'Rejection reason must be at least 5 characters.',
        ]);

        /** @var User|null $admin */
        $admin = auth('api')->user();
        $product = $this->productService->rejectProduct($product, $validated['reason'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Product rejected and hidden from the platform. Industry owner notified.',
            'data' => (new IndustryProductResource($product, null, isIndustryContext: true))->resolve($request),
        ]);
    }

    /**
     * Delete a product as Admin.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $product = $this->findProduct($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed successfully.',
        ]);
    }

    protected function normalizeScopeInputs(Request $request): void
    {
        if ($request->filled('network_type')) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $request->input('network_type')));
            if (str_contains($rawNet, 'neuro')) {
                $request->merge(['network_type' => 'neuroscience']);
            } elseif (str_contains($rawNet, 'psych')) {
                $request->merge(['network_type' => 'psychology']);
            }
        }

        if ($request->filled('industry_type')) {
            $rawInd = strtolower(trim((string) $request->input('industry_type')));
            if ($rawInd === 'biotech') {
                $request->merge(['industry_type' => 'biotechnology']);
            }
        }
    }

    protected function expireProductFlags(): void
    {
        IndustryProduct::where('is_boosted', true)
            ->whereNotNull('boost_expires_at')
            ->where('boost_expires_at', '<=', now())
            ->update(['is_new_release' => false, 'is_boosted' => false]);

        IndustryProduct::where('is_new_release', true)
            ->whereNotNull('new_release_expires_at')
            ->where('new_release_expires_at', '<=', now())
            ->update(['is_new_release' => false]);
    }

    /**
     * @return array{total_products: int, active_products: int, inactive_products: int, draft_products: int, rejected_products: int}
     */
    protected function buildSummary(string $networkType, string $industryType, ?int $sectionId = null): array
    {
        $query = IndustryProduct::query()
            ->where('network_type', $networkType)
            ->where('industry_type', $industryType);

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        $stats = $query
            ->selectRaw("COUNT(*) as total_products, COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products, COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_products, COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_products, COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_products")
            ->first();

        return [
            'total_products' => (int) ($stats->total_products ?? 0),
            'active_products' => (int) ($stats->active_products ?? 0),
            'inactive_products' => (int) ($stats->inactive_products ?? 0),
            'draft_products' => (int) ($stats->draft_products ?? 0),
            'rejected_products' => (int) ($stats->rejected_products ?? 0),
        ];
    }

    protected function filteredProductsQuery(
        Request $request,
        string $networkType,
        string $industryType,
        string $statusFilter,
        bool $applyCategoryFilter = true
    ): Builder {
        $query = IndustryProduct::query()
            ->where('network_type', $networkType)
            ->where('industry_type', $industryType);

        if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'inactive', 'draft', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }

        if ($applyCategoryFilter && $request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $sanitized = addcslashes($search, '%_');
                $query->where(function (Builder $q) use ($search, $sanitized): void {
                    $q->where('product_id', $search)
                        ->orWhere('product_id', 'LIKE', "{$sanitized}%")
                        ->orWhere('product_name', 'LIKE', "%{$sanitized}%")
                        ->orWhereHas('industry', function (Builder $industryQuery) use ($sanitized): void {
                            $industryQuery->where('name', 'LIKE', "%{$sanitized}%");
                        });
                });
            }
        }

        return $query;
    }

    /**
     * @param  array<int, string>  $relations
     */
    protected function findProduct(int|string $id, array $relations = []): IndustryProduct
    {
        $query = IndustryProduct::query()
            ->where(function (Builder $q) use ($id): void {
                $q->where('id', $id)
                    ->orWhere('product_id', (string) $id)
                    ->orWhere('slug', (string) $id);
            });

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query->firstOrFail();
    }
}

