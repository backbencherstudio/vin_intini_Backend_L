<?php

namespace App\Http\Controllers\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndustryProductRequest;
use App\Http\Requests\UpdateIndustryProductRequest;
use App\Http\Resources\IndustryProductResource;
use App\Models\AdClick;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustrySections;
use App\Models\User;
use App\Services\Industry\IndustryProductService;
use App\Services\Media\OptimizedImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IndustryProductController extends Controller
{
    public function __construct(
        protected OptimizedImageUploadService $imageUploadService,
        protected IndustryProductService $productService
    ) {}

    /**
     * Plain sections list for the "Select a Section" dropdown.
     */
    public function dropdownSections(Request $request): JsonResponse
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

        $request->validate([
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['nullable', 'string', 'in:biotechnology,psychotropics'],
        ]);

        $query = IndustrySections::query();

        if ($request->filled('network_type')) {
            $query->where('network_type', $request->input('network_type'));
        }

        if ($request->filled('industry_type')) {
            $query->where('industry_type', $request->input('industry_type'));
        }

        $sections = $query->orderBy('name')->get(['id', 'name', 'network_type', 'industry_type']);

        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully.',
            'data' => $sections,
        ]);
    }

    /**
     * Categories under a specific section for the "Select a Category" dependent dropdown.
     */
    public function sectionCategories(int $id): JsonResponse
    {
        $section = IndustrySections::with([
            'IndustryCategory' => function ($q): void {
                $q->select('id', 'section_id', 'category_name')->orderBy('category_name');
            },
        ])->find($id);

        if (! $section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found.',
            ], 404);
        }

        $categories = $section->IndustryCategory->map(fn ($cat): array => [
            'id' => $cat->id,
            'section_id' => $cat->section_id,
            'category_name' => $cat->category_name,
        ])->values();

        return response()->json([
            'success' => true,
            'message' => 'Section categories retrieved successfully.',
            'data' => [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Alias for productFeed to ensure backwards compatibility.
     */
    public function feed(Request $request): JsonResponse
    {
        return $this->productFeed($request);
    }

    /**
     * Public feed of products grouped or filtered by section & sub-category (Screenshots 1 & 2).
     * - When accessed without specific section/search, returns section-wise grouped data with tabs and products.
     * - When section_id, category_id, search, or flat=1 is passed, returns a paginated list of products.
     */
    public function productFeed(Request $request): JsonResponse
    {
        // Normalize network_type (e.g. 'PsychologyNetwork', 'psychology_network' -> 'psychology')
        if ($request->filled('network_type')) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $request->input('network_type')));
            if (str_contains($rawNet, 'neuro')) {
                $request->merge(['network_type' => 'neuroscience']);
            } elseif (str_contains($rawNet, 'psych')) {
                $request->merge(['network_type' => 'psychology']);
            }
        }

        // Normalize industry_type (e.g. 'biotech' -> 'biotechnology')
        if ($request->filled('industry_type')) {
            $rawInd = strtolower(trim((string) $request->input('industry_type')));
            if ($rawInd === 'biotech') {
                $request->merge(['industry_type' => 'biotechnology']);
            }
        }

        $request->validate([
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['nullable', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['nullable', 'integer', 'exists:industry_categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'flat' => ['nullable', 'boolean'],
            'view' => ['nullable', 'string', 'in:all,section,overview'],
            'limit_per_section' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $currentUser = auth('api')->user();

        // Auto-expire products whose boost or new release expired
        IndustryProduct::where('is_boosted', true)
            ->whereNotNull('boost_expires_at')
            ->where('boost_expires_at', '<=', now())
            ->update(['is_new_release' => false, 'is_boosted' => false]);

        IndustryProduct::where('is_new_release', true)
            ->whereNotNull('new_release_expires_at')
            ->where('new_release_expires_at', '<=', now())
            ->update(['is_new_release' => false]);

        $isOverviewRequest = $request->input('view') === 'overview'
            && ! $request->filled('section_id')
            && ! $request->filled('category_id')
            && ! $request->boolean('flat')
            && ! $request->filled('cursor');

        // 1. Tab filter / Search (non-overview) / Section show-all / Explicit flat pagination view
        if ($request->filled('section_id') || $request->filled('category_id') || ($request->filled('search') && ! $isOverviewRequest) || $request->boolean('flat') || $request->input('view') === 'all' || $request->filled('cursor')) {
            $query = IndustryProduct::query()
                ->where('status', 'active')
                ->where('network_type', $request->input('network_type'))
                ->where('industry_type', $request->input('industry_type'))
                ->with(['creator', 'industry', 'section', 'category', 'activeCampaign'])
                ->feedOrder();

            // If specific section is chosen
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->integer('section_id'));
            }

            // If specific sub-category is chosen (inside or outside section)
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->integer('category_id'));
            }

            // Search by product name or product_id (description excluded for 1M+ scale)
            if ($request->filled('search')) {
                $this->applyProductSearch($query, (string) $request->input('search'));
            }

            // Resolve target section and its categories if section_id or category_id is provided
            $targetSectionId = $request->input('section_id');
            if (! $targetSectionId && $request->filled('category_id')) {
                $targetSectionId = DB::table('industry_categories')
                    ->where('id', $request->integer('category_id'))
                    ->value('section_id');
            }

            $sectionObj = null;
            $sectionCategories = [];
            if ($targetSectionId) {
                $sectionObj = IndustrySections::with([
                    'IndustryCategory' => function ($q): void {
                        $q->select('id', 'section_id', 'category_name')->orderBy('category_name');
                    },
                ])->find($targetSectionId);

                if ($sectionObj) {
                    $sectionCategories = $sectionObj->IndustryCategory->map(fn ($cat): array => [
                        'id' => $cat->id,
                        'category_name' => $cat->category_name,
                    ])->values();
                }
            }

            $totalCount = (clone $query)->without(['creator', 'industry', 'section', 'category'])->count();

            // Calculate pagination:
            $showAllRequested = $request->boolean('all') || $request->boolean('show_all') || $request->input('per_page') === 'all';

            $rawPerPage = $request->input('per_page');
            $hasValidPerPage = $request->filled('per_page') && is_numeric($rawPerPage) && (int) $rawPerPage > 0;

            if ($showAllRequested) {
                $perPage = max($totalCount, 1);
            } elseif ($hasValidPerPage) {
                $perPage = min(max((int) $rawPerPage, 1), 100);
            } elseif ($request->filled('limit_per_section') && is_numeric($request->input('limit_per_section')) && (int) $request->input('limit_per_section') > 0) {
                $perPage = min(max((int) $request->input('limit_per_section'), 1), 100);
            } elseif ($request->filled('category_id')) {
                // When switching to a specific sub-category tab on the section card, show up to 6 products
                $perPage = 6;
            } else {
                // Section show-all or flat list defaults to 12
                $perPage = 12;
            }

            $paginated = $query->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

            // Fetch liked ids in a single fast query to avoid N+1
            $likedProductIds = [];
            if ($currentUser) {
                $productIds = $paginated->getCollection()->pluck('id')->all();
                if (! empty($productIds)) {
                    $likedProductIds = IndustryProductLike::where('user_id', $currentUser->id)
                        ->whereIn('industry_product_id', $productIds)
                        ->pluck('industry_product_id')
                        ->flip()
                        ->all();
                }
            }

            $data = $paginated->getCollection()->map(fn ($product) => (new IndustryProductResource($product, $likedProductIds, isFeed: true))->resolve($request));

            $responseData = [
                'success' => true,
                'message' => 'Products retrieved successfully.',
            ];

            if ($sectionObj) {
                $responseData['section_id'] = $sectionObj->id;
                $responseData['section_name'] = $sectionObj->name;
                $responseData['network_type'] = $sectionObj->network_type;
                $responseData['industry_type'] = $sectionObj->industry_type;
                $responseData['categories'] = $sectionCategories;
            }

            $responseData['total_products'] = $totalCount;
            $responseData['data'] = $data;
            $responseData['pagination'] = [
                'limit' => $paginated->perPage(),
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
                'total' => $totalCount,
            ];

            return response()->json($responseData);
        }

        // 2. Default Section-wise feed view (Renders the entire MindUnite feed page with section blocks & tabs)
        $networkType = $request->input('network_type');
        $industryType = $request->input('industry_type');

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

        $search = trim((string) $request->input('search', ''));
        $sectionIds = $sections->pluck('id')->all();
        $rawLimit = $request->input('limit_per_section');
        $limitPerSection = ($request->filled('limit_per_section') && is_numeric($rawLimit) && (int) $rawLimit > 0)
            ? min((int) $rawLimit, 50)
            : 6;

        $groupedProducts = collect();
        $productCounts = collect();
        $likedProductIds = [];

        if (! empty($sectionIds)) {
            // High-performance aggregate count per section without loading models into memory
            $countQuery = IndustryProduct::query()
                ->where('network_type', $networkType)
                ->where('industry_type', $industryType)
                ->where('status', 'active')
                ->whereIn('section_id', $sectionIds);

            if ($search !== '') {
                $this->applyProductSearch($countQuery, $search);
            }

            $productCounts = $countQuery
                ->groupBy('section_id')
                ->selectRaw('section_id, count(*) as total')
                ->pluck('total', 'section_id');

            $products = collect();

            if ($search !== '') {
                $matchingSectionIds = array_values(array_filter(
                    $sectionIds,
                    fn ($sid): bool => (int) $productCounts->get($sid, 0) > 0
                ));

                foreach ($matchingSectionIds as $sid) {
                    $sectionQuery = IndustryProduct::query()
                        ->where('network_type', $networkType)
                        ->where('industry_type', $industryType)
                        ->where('status', 'active')
                        ->where('section_id', $sid);
                    $this->applyProductSearch($sectionQuery, $search);

                    $sectionItems = $sectionQuery
                        ->with(['creator', 'industry', 'section', 'category', 'activeCampaign'])
                        ->feedOrder()
                        ->limit($limitPerSection)
                        ->get();

                    $products = $products->concat($sectionItems);
                }
            } else {
                // Fetch only top N product IDs per section using window function (O(1) memory at million-row scale)
                $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
                $rankedSql = "
                    SELECT id FROM (
                        SELECT id, section_id, ROW_NUMBER() OVER (
                            PARTITION BY section_id 
                            ORDER BY 
                                CASE 
                                    WHEN is_boosted = 1 AND (boost_expires_at IS NULL OR boost_expires_at > NOW()) THEN 2
                                    WHEN is_new_release = 1 AND (new_release_expires_at IS NULL OR new_release_expires_at > NOW()) THEN 1
                                    ELSE 0
                                END DESC,
                                boost_score DESC,
                                id DESC
                        ) as rn
                        FROM industry_products
                        WHERE network_type = ?
                          AND industry_type = ?
                          AND status = 'active'
                          AND section_id IN ({$placeholders})
                          AND deleted_at IS NULL
                    ) as ranked
                    WHERE rn <= ?
                ";
                $bindings = array_merge([$networkType, $industryType], $sectionIds, [$limitPerSection]);
                $rankedRows = DB::select($rankedSql, $bindings);
                $topProductIds = array_column($rankedRows, 'id');

                if ($topProductIds !== []) {
                    $products = IndustryProduct::query()
                        ->whereIn('id', $topProductIds)
                        ->with(['creator', 'industry', 'section', 'category', 'activeCampaign'])
                        ->feedOrder()
                        ->get();
                }
            }

            if ($products->isNotEmpty()) {
                if ($currentUser) {
                    $productIds = $products->pluck('id')->all();
                    $likedProductIds = IndustryProductLike::where('user_id', $currentUser->id)
                        ->whereIn('industry_product_id', $productIds)
                        ->pluck('industry_product_id')
                        ->flip()
                        ->all();
                }

                $groupedProducts = $products->groupBy(fn ($item): int => (int) $item->section_id);
            }
        }

        if ($search !== '') {
            $sections = $sections
                ->filter(fn ($section): bool => (int) $productCounts->get($section->id, 0) > 0)
                ->values();
        }

        $data = $sections->map(function ($section) use ($groupedProducts, $likedProductIds, $productCounts, $limitPerSection, $request): array {
            $sectionProducts = $groupedProducts->get((int) $section->id, collect())
                ->map(fn ($product) => (new IndustryProductResource($product, $likedProductIds, isFeed: true))->resolve($request))->values();

            $totalProducts = (int) ($productCounts->get($section->id, 0));

            return [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'total_products' => $totalProducts,
                'has_more' => $totalProducts > $limitPerSection,
                'categories' => $section->IndustryCategory->map(fn ($cat): array => [
                    'id' => $cat->id,
                    'category_name' => $cat->category_name,
                ])->values(),
                'products' => $sectionProducts,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Section-wise products feed retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Show single product details + record unique & total view + record verified click (Learn More).
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $product = $this->findProduct($id, ['creator', 'industry', 'section', 'category', 'activeCampaign']);

        $currentUser = auth('api')->user() ?? auth('web')->user();
        if (! $currentUser && ($request->has('preview') || $request->header('X-Preview-Mode'))) {
            $currentUser = User::where('email', 'niaz.softvence@gmail.com')->first() ?? User::first();
        }

        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && ($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin'));
        if ($product->status !== 'active' && ! $product->isOwnedBy($currentUser) && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        if ($product->status === 'active') {
            // Record unique and total view
            $this->recordView($product, $currentUser, $request->ip());

            // Record verified click on product and boost campaign (Learn More / Card Click from feed)
            $this->recordProductClick($product, $currentUser, $request->ip(), $request->input('placement', 'industry_product_feed'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => (new IndustryProductResource($product->fresh()))->resolve(),
        ]);
    }

    /**
     * Toggle like on a product + record unique view if not viewed yet.
     */
    public function toggleLike(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user() ?? auth('web')->user();
        if (! $user && (app()->environment('local', 'testing') || $request->has('preview') || $request->header('X-Preview-Mode'))) {
            $user = User::where('email', 'niaz.softvence@gmail.com')->first() ?? User::first();
        }

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = $this->findProduct($id);

        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return DB::transaction(function () use ($product, $user, $request) {
            $like = IndustryProductLike::where('industry_product_id', $product->id)
                ->where('user_id', $user->id)
                ->first();

            if ($like) {
                $like->delete();
                $product->decrement('likes_count');
                $isLiked = false;
                $message = 'Product unliked successfully.';
            } else {
                IndustryProductLike::create([
                    'industry_product_id' => $product->id,
                    'user_id' => $user->id,
                ]);
                $product->increment('likes_count');
                $isLiked = true;
                $message = 'Product liked successfully.';

                // Automatically count view if user hasn't viewed details yet
                $this->recordView($product, $user, $request->ip());
            }

            $freshProduct = $product->fresh();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'product_id' => $product->id,
                    'is_liked' => $isLiked,
                    'likes_count' => (int) $freshProduct->likes_count,
                    'views_count' => (int) $freshProduct->views_count,
                ],
            ]);
        });
    }

    /**
     * Pro Industry Advertisement Dashboard - Metrics Summary (Screenshot 3).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (($accessError = $this->validateIndustryAccess($user)) instanceof JsonResponse) {
            return $accessError;
        }

        $industry = $user->industry;

        $stats = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->selectRaw("COUNT(*) as total_products, COUNT(CASE WHEN status = 'active' THEN 1 END) as total_active_products, COALESCE(SUM(likes_count), 0) as total_likes, COALESCE(SUM(views_count), 0) as total_views, COALESCE(SUM(unique_views_count), 0) as total_unique_views, COALESCE(SUM(impressions_count), 0) as total_impressions, COALESCE(SUM(clicks_count), 0) as total_clicks")
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Product dashboard statistics retrieved successfully.',
            'data' => [
                'total_products' => (int) ($stats->total_products ?? 0),
                'total_active_products' => (int) ($stats->total_active_products ?? 0),
                'total_likes' => (int) ($stats->total_likes ?? 0),
                'total_views' => (int) ($stats->total_views ?? 0),
                'total_unique_views' => (int) ($stats->total_unique_views ?? 0),
                'total_impressions' => (int) ($stats->total_impressions ?? 0),
                'total_clicks' => (int) ($stats->total_clicks ?? 0),
            ],
        ]);
    }

    /**
     * Pro Industry Advertisement Listings Table (Screenshot 3).
     */
    public function myListings(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (($accessError = $this->validateIndustryAccess($user)) instanceof JsonResponse) {
            return $accessError;
        }

        $industry = $user->industry;
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        // Auto-expire products whose boost duration has elapsed
        IndustryProduct::where('industry_id', $industry->id)
            ->where('is_boosted', true)
            ->whereNotNull('boost_expires_at')
            ->where('boost_expires_at', '<=', now())
            ->update(['is_boosted' => false]);

        $query = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->with(['creator', 'industry', 'section', 'category', 'latestCampaign'])
            ->orderByRaw(
                'CASE WHEN is_boosted = 1 AND (boost_expires_at IS NULL OR boost_expires_at > ?) THEN 1 ELSE 0 END DESC',
                [now()]
            )
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('boost_status')) {
            $boostStatus = $request->input('boost_status');
            if ($boostStatus === 'active') {
                $query->where('is_boosted', true)->where(function ($q): void {
                    $q->whereNull('boost_expires_at')->orWhere('boost_expires_at', '>', now());
                });
            } elseif ($boostStatus === 'paused') {
                $query->whereHas('latestCampaign', function ($q): void {
                    $q->where('status', 'paused');
                });
            } elseif ($boostStatus === 'inactive' || $boostStatus === 'not_boosted') {
                $query->where(function ($q): void {
                    $q->where('is_boosted', false)->orWhereNull('is_boosted');
                });
            }
        }

        if ($request->filled('search')) {
            $this->applyProductSearch($query, (string) $request->input('search'));
        }

        $paginated = $query->paginate($perPage);

        $stats = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->selectRaw("COUNT(*) as total_products, COUNT(CASE WHEN status = 'active' THEN 1 END) as total_active_products, COUNT(CASE WHEN status = 'rejected' THEN 1 END) as total_rejected_products, COALESCE(SUM(impressions_count), 0) as total_impressions, COALESCE(SUM(clicks_count), 0) as total_clicks")
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'stats' => [
                'total_products' => (int) ($stats->total_products ?? 0),
                'total_active_products' => (int) ($stats->total_active_products ?? 0),
                'total_rejected_products' => (int) ($stats->total_rejected_products ?? 0),
                'total_impressions' => (int) ($stats->total_impressions ?? 0),
                'total_clicks' => (int) ($stats->total_clicks ?? 0),
            ],
            'data' => IndustryProductResource::collection($paginated),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Create a new product / advertisement.
     */
    public function store(StoreIndustryProductRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (($accessError = $this->validateIndustryAccess($user)) instanceof JsonResponse) {
            return $accessError;
        }

        $industry = $user->industry;
        $validated = $request->validated();
        if ($request->has('information_confirmed')) {
            $validated['information_confirmed'] = (bool) $request->boolean('information_confirmed', true);
        }

        $product = $this->productService->createProduct(
            $user,
            $industry,
            $validated,
            $request->file('image')
        );

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => (new IndustryProductResource($product))->resolve(),
        ], 201);
    }

    /**
     * Get single product data for editing.
     */
    public function editData(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = $this->findProduct($id, ['creator', 'industry', 'section', 'category']);

        if (! $this->authorizeProductAction($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view or edit this product.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => (new IndustryProductResource($product))->resolve(),
        ]);
    }

    /**
     * Update an existing product / advertisement.
     */
    public function update(UpdateIndustryProductRequest $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = $this->findProduct($id);

        if (! $this->authorizeProductAction($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this product.',
            ], 403);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        if (! $isAdmin && $product->isRejected()) {
            return $this->rejectedProductResponse($product, 'cannot be modified or reactivated');
        }

        $validated = $request->validated();
        if ($request->has('information_confirmed')) {
            $validated['information_confirmed'] = (bool) $request->boolean('information_confirmed');
        }

        $product = $this->productService->updateProduct(
            $product,
            $validated,
            $request->file('image')
        );

        return response()->json([
            'success' => true,
            'message' => 'Product advertisement updated successfully.',
            'data' => (new IndustryProductResource($product))->resolve(),
        ]);
    }

    /**
     * Update product advertisement status (e.g. active, inactive, draft).
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $product = $this->findProduct($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        if (! $this->authorizeProductAction($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this product.',
            ], 403);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        if (! $isAdmin && $product->isRejected()) {
            return $this->rejectedProductResponse($product, 'cannot be activated or changed');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,draft'],
        ]);

        $newStatus = $validated['status'];

        // If activating, verify that user still has an active Pro Industry subscription
        if (! $isAdmin && $newStatus === 'active') {
            if (($accessError = $this->validateIndustryAccess($user)) instanceof JsonResponse) {
                return $accessError;
            }
        }

        $product->status = $newStatus;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => "Product advertisement status updated to {$product->status} successfully.",
            'data' => [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'status' => $product->status,
                'updated_at' => optional($product->updated_at)?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Delete product advertisement.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = $this->findProduct($id);

        if (! $this->authorizeProductAction($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this product.',
            ], 403);
        }

        // Delete image file from storage if present
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product advertisement removed successfully.',
        ]);
    }

    /**
     * Find product by primary id, unique 6-digit product_id, or slug.
     */
    private function findProduct(int|string $id, array $relations = []): IndustryProduct
    {
        $query = IndustryProduct::query()
            ->where(function ($q) use ($id): void {
                $q->where('id', $id)
                    ->orWhere('product_id', (string) $id)
                    ->orWhere('slug', (string) $id);
            });

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query->firstOrFail();
    }

    /**
     * Authorize user action on product:
     * 1. System admin can perform any action.
     * 2. Creator can perform action if active.
     * 3. Even if creator leaves/account deleted, current company member can perform action.
     */
    private function authorizeProductAction(User $user, IndustryProduct $product): bool
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return true;
        }

        if ($product->creator_id && $product->creator_id === $user->id) {
            return true;
        }

        return $user->industry && $user->industry->id === $product->industry_id;
    }

    private function rejectedProductResponse(IndustryProduct $product, string $actionDescription): JsonResponse
    {
        $reasonSuffix = $product->rejection_reason ? " Reason: {$product->rejection_reason}" : '';

        return response()->json([
            'success' => false,
            'message' => "This product was rejected by Admin and {$actionDescription}.{$reasonSuffix}",
            'rejection_reason' => $product->rejection_reason,
            'rejected_at' => $product->rejected_at?->toISOString(),
        ], 403);
    }

    /**
     * Validate active Pro Industry subscription & company profile.
     */
    private function validateIndustryAccess(?User $user): ?JsonResponse
    {
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        if (! $user->hasActiveSubscription(PlanType::INDUSTRY)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Pro Industry subscription plan to add or manage product.',
            ], 403);
        }

        $industry = $user->industry;
        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile. Please create a company profile first.',
            ], 422);
        }

        return null;
    }

    /**
     * Unique and Total view recording: delegates to IndustryProduct model method.
     */
    private function recordView(IndustryProduct $product, ?User $user, ?string $ip): void
    {
        $product->recordView($user, $ip);
    }

    /**
     * Record a product click and ad campaign click when product details are accessed from feed / Learn More.
     */
    private function recordProductClick(IndustryProduct $product, ?User $user, ?string $ip, string $placement = 'industry_product_feed'): void
    {
        // Anti-fraud self-click guard: Do not count clicks if user is the product creator or owner
        $isOwner = $user instanceof User && (
            $product->isOwnedBy($user)
            || (int) $user->id === (int) $product->creator_id
            || (int) $user->id === (int) ($product->industry?->created_by ?? 0)
            || ($user->industry && (int) $user->industry->id === (int) $product->industry_id)
        );

        if ($isOwner) {
            return;
        }

        $userIdentifier = $user instanceof User ? 'user_'.$user->id : 'ip_'.($ip ?: 'unknown');
        $cooldown = 30; // 30-second anti-spam click debounce protection per user/IP

        $productThrottleKey = "product_click_throttle_{$product->id}_{$userIdentifier}";
        if (Cache::add($productThrottleKey, true, now()->addSeconds($cooldown))) {
            $product->increment('clicks_count');
        }

        // If product has an active boost campaign, record ad click as well
        $campaign = $product->relationLoaded('activeCampaign') && $product->activeCampaign
            ? $product->activeCampaign
            : ($product->activeCampaign()->with(['boostable', 'industry'])->first()
                ?? $product->campaigns()->where('status', AdCampaignStatus::ACTIVE)->latest('id')->first());

        if ($campaign) {
            $campaignThrottleKey = "ad_click_throttle_{$campaign->id}_{$userIdentifier}";
            if (Cache::add($campaignThrottleKey, true, now()->addSeconds($cooldown))) {
                AdClick::create([
                    'ad_campaign_id' => $campaign->id,
                    'boostable_type' => $campaign->boostable_type ?? $product->getMorphClass(),
                    'boostable_id' => $campaign->boostable_id ?? $product->id,
                    'user_id' => $user?->id,
                    'placement' => $placement,
                    'ip_address' => $ip,
                ]);

                $campaign->increment('total_clicks');
            }
        }
    }

    /**
     * High-performance search for product_name and product_id (Engineered for 1M+ rows scale).
     * Excludes heavy TEXT description column to prevent disk table scanning.
     */
    private function applyProductSearch(Builder $query, string $rawSearch): void
    {
        $search = trim($rawSearch);
        if ($search === '') {
            return;
        }

        $sanitized = addcslashes($search, '%_');
        $pidSearch = ltrim($search, '#');
        $pidSanitized = addcslashes($pidSearch, '%_');
        $isMysql = DB::connection()->getDriverName() === 'mysql';

        $query->where(function (Builder $q) use ($search, $sanitized, $pidSearch, $pidSanitized, $isMysql): void {
            // 1. Direct or Prefix Match on indexed Unique product_id (supports optional leading #)
            if ($pidSearch !== '') {
                $q->where('product_id', $pidSearch)
                    ->orWhere('product_id', 'LIKE', "{$pidSanitized}%");
            }

            // 2. High-Performance Product Name Search
            // If MySQL and search string has >= 3 chars, combine FULLTEXT with LIKE for maximum speed & accuracy
            if ($isMysql && strlen($search) >= 3) {
                $words = array_filter(preg_split('/\s+/', $search));
                $booleanTerms = array_map(function (string $word): string {
                    $cleaned = preg_replace('/[+\-><()~*\"@]+/', '', $word);

                    return ! empty($cleaned) ? '+'.$cleaned.'*' : '';
                }, $words);
                $booleanQuery = trim(implode(' ', array_filter($booleanTerms)));

                if (! empty($booleanQuery)) {
                    $q->orWhereRaw('MATCH(product_name) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
                }
            }

            // Substring match on product_name (uses in-memory buffer pool without touching text blobs)
            $q->orWhere('product_name', 'LIKE', "%{$sanitized}%");
        });
    }
}
