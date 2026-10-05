<?php

namespace App\Http\Controllers\Api;

use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndustryProductRequest;
use App\Http\Requests\UpdateIndustryProductRequest;
use App\Http\Resources\IndustryProductResource;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustryProductView;
use App\Models\IndustrySections;
use App\Models\User;
use App\Services\OptimizedImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndustryProductController extends Controller
{
    public function __construct(
        protected OptimizedImageUploadService $imageUploadService
    ) {}

    /**
     * Categories and Sub-categories for dynamic cascading dropdowns.
     */
    public function dropdownCategories(Request $request): JsonResponse
    {
        $request->validate([
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['nullable', 'string', 'in:biotechnology,psychotropics'],
        ]);

        $query = IndustrySections::query()
            ->with([
                'IndustryCategory' => function ($q) {
                    $q->select('id', 'section_id', 'category_name')->orderBy('category_name');
                },
            ]);

        if ($request->filled('network_type')) {
            $query->where('network_type', $request->input('network_type'));
        }

        if ($request->filled('industry_type')) {
            $query->where('industry_type', $request->input('industry_type'));
        }

        $sections = $query->orderBy('name')->get();

        $formatted = $sections->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'categories' => $section->IndustryCategory->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'section_id' => $cat->section_id,
                        'category_name' => $cat->category_name,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $formatted,
        ]);
    }

    /**
     * Public feed of products grouped or filtered by section & sub-category (Screenshots 1 & 2).
     * - When accessed without specific section/search, returns section-wise grouped data with tabs and products.
     * - When section_id, category_id, search, or flat=1 is passed, returns a paginated list of products.
     */
    public function feed(Request $request): JsonResponse
    {
        $request->validate([
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'industry_type' => ['required', 'string', 'in:biotechnology,psychotropics'],
            'section_id' => ['nullable', 'integer', 'exists:industry_sections,id'],
            'category_id' => ['nullable', 'integer', 'exists:industry_categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'flat' => ['nullable', 'boolean'],
            'limit_per_section' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $currentUser = auth('api')->user();

        // 1. Tab filter / Search / Explicit flat pagination view
        if ($request->filled('section_id') || $request->filled('category_id') || $request->filled('search') || $request->boolean('flat')) {
            $perPage = (int) $request->input('per_page', 12);

            $query = IndustryProduct::query()
                ->where('network_type', $request->input('network_type'))
                ->where('industry_type', $request->input('industry_type'))
                ->where('status', 'active')
                ->with(['creator', 'industry', 'section', 'category'])
                ->latest('id');

            // If specific section is chosen
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->integer('section_id'));
            }

            // If specific sub-category is chosen (inside or outside section)
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->integer('category_id'));
            }

            // Search by product name or tags
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $paginated = $query->paginate($perPage);

            // Fetch liked ids in a single fast query to avoid N+1
            $likedProductIds = [];
            if ($currentUser) {
                $productIds = $paginated->pluck('id')->all();
                if (! empty($productIds)) {
                    $likedProductIds = IndustryProductLike::where('user_id', $currentUser->id)
                        ->whereIn('industry_product_id', $productIds)
                        ->pluck('industry_product_id')
                        ->flip()
                        ->all();
                }
            }

            $data = $paginated->getCollection()->map(function ($product) use ($likedProductIds) {
                return (new IndustryProductResource($product, $likedProductIds))->resolve();
            });

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully.',
                'data' => $data,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                    'has_more_pages' => $paginated->hasMorePages(),
                ],
            ]);
        }

        // 2. Default Section-wise feed view (Renders the entire MindUnite feed page with section blocks & tabs)
        $sections = IndustrySections::query()
            ->where('network_type', $request->input('network_type'))
            ->where('industry_type', $request->input('industry_type'))
            ->with([
                'IndustryCategory' => function ($q) {
                    $q->select('id', 'section_id', 'category_name')->orderBy('category_name');
                },
            ])
            ->orderBy('id')
            ->get();

        $sectionIds = $sections->pluck('id')->all();
        $limitPerSection = (int) $request->input('limit_per_section', 12);

        $products = IndustryProduct::query()
            ->where('network_type', $request->input('network_type'))
            ->where('industry_type', $request->input('industry_type'))
            ->where('status', 'active')
            ->whereIn('section_id', $sectionIds)
            ->with(['creator', 'industry', 'section', 'category'])
            ->latest('id')
            ->get();

        $likedProductIds = [];
        if ($currentUser) {
            $productIds = $products->pluck('id')->all();
            if (! empty($productIds)) {
                $likedProductIds = IndustryProductLike::where('user_id', $currentUser->id)
                    ->whereIn('industry_product_id', $productIds)
                    ->pluck('industry_product_id')
                    ->flip()
                    ->all();
            }
        }

        $groupedProducts = $products->groupBy('section_id');

        $data = $sections->map(function ($section) use ($groupedProducts, $likedProductIds, $limitPerSection) {
            $sectionProducts = $groupedProducts->get($section->id, collect())
                ->take($limitPerSection)
                ->map(function ($product) use ($likedProductIds) {
                    return (new IndustryProductResource($product, $likedProductIds))->resolve();
                })->values();

            return [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'network_type' => $section->network_type,
                'industry_type' => $section->industry_type,
                'categories' => $section->IndustryCategory->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'category_name' => $cat->category_name,
                    ];
                })->values(),
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
     * Show single product details + record unique view.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $product = IndustryProduct::query()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->with(['creator', 'industry', 'section', 'category'])
            ->firstOrFail();

        $currentUser = auth('api')->user();

        // Record unique view
        $this->recordView($product, $currentUser, $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => (new IndustryProductResource($product->fresh()))->resolve(),
        ]);
    }

    /**
     * Toggle like on a product + record unique view if not viewed yet.
     */
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = IndustryProduct::findOrFail($id);

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
        if ($accessError = $this->validateIndustryAccess($user)) {
            return $accessError;
        }

        $industry = $user->industry;

        $stats = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->selectRaw('COUNT(*) as total_advertisements, COALESCE(SUM(likes_count), 0) as total_likes, COALESCE(SUM(views_count), 0) as total_views')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement dashboard statistics retrieved successfully.',
            'data' => [
                'total_advertisements' => (int) ($stats->total_advertisements ?? 0),
                'total_likes' => (int) ($stats->total_likes ?? 0),
                'total_views' => (int) ($stats->total_views ?? 0),
            ],
        ]);
    }

    /**
     * Pro Industry Advertisement Listings Table (Screenshot 3).
     */
    public function myListings(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if ($accessError = $this->validateIndustryAccess($user)) {
            return $accessError;
        }

        $industry = $user->industry;
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $query = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->with(['creator', 'industry', 'section', 'category'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Advertisements retrieved successfully.',
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
        if ($accessError = $this->validateIndustryAccess($user)) {
            return $accessError;
        }

        $industry = $user->industry;
        $validated = $request->validated();

        // Handle Image Upload with optimization
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageUploadService->store($request->file('image'), 'products');
        }

        // Format tags
        $tags = $this->formatTags($request->input('tags'));

        // Generate clean unique slug
        $baseSlug = Str::slug($validated['product_name']);
        $uniqueSlug = $baseSlug.'-'.Str::lower(Str::random(6));

        $product = IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => $validated['network_type'],
            'industry_type' => $validated['industry_type'],
            'section_id' => $validated['section_id'],
            'category_id' => $validated['category_id'],
            'product_name' => $validated['product_name'],
            'slug' => $uniqueSlug,
            'description' => $validated['description'],
            'product_url' => $validated['product_url'],
            'image' => $imagePath,
            'tags' => $tags,
            'information_confirmed' => (bool) $request->boolean('information_confirmed', true),
            'status' => $validated['status'] ?? 'active',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $product->load(['creator', 'industry', 'section', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Product advertisement created successfully.',
            'data' => (new IndustryProductResource($product))->resolve(),
        ], 201);
    }

    /**
     * Get single product data for editing.
     */
    public function editData(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = IndustryProduct::with(['creator', 'industry', 'section', 'category'])->findOrFail($id);

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
    public function update(UpdateIndustryProductRequest $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = IndustryProduct::findOrFail($id);

        if (! $this->authorizeProductAction($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this product.',
            ], 403);
        }

        $validated = $request->validated();

        // Handle Image Replacement
        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $product->image = $this->imageUploadService->store($request->file('image'), 'products');

            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        if (isset($validated['product_name']) && $validated['product_name'] !== $product->product_name) {
            $product->product_name = $validated['product_name'];
            $product->slug = Str::slug($validated['product_name']).'-'.Str::lower(Str::random(6));
        }

        if (isset($validated['network_type'])) {
            $product->network_type = $validated['network_type'];
        }
        if (isset($validated['industry_type'])) {
            $product->industry_type = $validated['industry_type'];
        }
        if (isset($validated['section_id'])) {
            $product->section_id = $validated['section_id'];
        }
        if (isset($validated['category_id'])) {
            $product->category_id = $validated['category_id'];
        }
        if (isset($validated['description'])) {
            $product->description = $validated['description'];
        }
        if (isset($validated['product_url'])) {
            $product->product_url = $validated['product_url'];
        }
        if ($request->has('tags')) {
            $product->tags = $this->formatTags($request->input('tags'));
        }
        if ($request->has('information_confirmed')) {
            $product->information_confirmed = (bool) $request->boolean('information_confirmed');
        }
        if (isset($validated['status'])) {
            $product->status = $validated['status'];
        }

        $product->save();
        $product->load(['creator', 'industry', 'section', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Product advertisement updated successfully.',
            'data' => (new IndustryProductResource($product))->resolve(),
        ]);
    }

    /**
     * Delete product advertisement.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $product = IndustryProduct::findOrFail($id);

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

        if ($user->industry && $user->industry->id === $product->industry_id) {
            return true;
        }

        return false;
    }

    /**
     * Validate active Pro Industry subscription & company profile.
     */
    private function validateIndustryAccess(?User $user): ?JsonResponse
    {
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        if (! $user->hasActiveSubscription(PlanType::INDUSTRY)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Pro Industry subscription plan to add or manage product advertisements.',
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
     * Unique view recording: exactly 1 view per user / IP.
     */
    private function recordView(IndustryProduct $product, ?User $user, ?string $ip): void
    {
        $viewCriteria = [
            'industry_product_id' => $product->id,
            'user_id' => $user?->id,
        ];

        if (! $user) {
            $viewCriteria['ip_address'] = $ip;
        }

        $view = IndustryProductView::firstOrCreate(
            $viewCriteria,
            ['ip_address' => $ip]
        );

        if ($view->wasRecentlyCreated) {
            $product->increment('views_count');
        }
    }

    /**
     * Parse tags array/string into clean array.
     */
    private function formatTags(mixed $tags): ?array
    {
        if (empty($tags)) {
            return null;
        }

        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (is_array($decoded)) {
                $tags = $decoded;
            } else {
                $tags = explode(',', $tags);
            }
        }

        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }

        return null;
    }
}
