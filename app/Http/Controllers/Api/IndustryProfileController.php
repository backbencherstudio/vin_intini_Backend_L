<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicationResource;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostLike;
use App\Models\IndustryJobPostSave;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\Publication;
use App\Models\PublicationLike;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Str;

class IndustryProfileController extends Controller
{
    /**
     * Get cursor-paginated job posts of a specific industry profile.
     */
    public function jobs(Request $request, string|int $identifier): JsonResponse
    {
        $industry = $this->findIndustry($identifier);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $query = IndustryJobPost::query()
            ->where('industry_id', $industry->id)
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_start_date')
                    ->orWhere('announcement_start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', $today);
            });

        // Optional search and filters
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_id', $search)
                    ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('work_mode')) {
            $query->where('work_mode', $request->input('work_mode'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        if ($request->filled('network_type')) {
            $query->where('network_type', $request->input('network_type'));
        }

        $perPage = min(max($request->integer('per_page', $request->integer('limit', 10)), 1), 100);
        $totalJobs = (clone $query)->count();

        $paginated = $query->with([
            'state:id,name',
            'city:id,name',
        ])
            ->withCount('applications')
            ->latest('id')
            ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        $currentUser = auth('api')->user() ?? auth()->user();

        $jobIds = $paginated->pluck('id')->all();
        $likedJobIds = [];
        $savedJobIds = [];
        $appliedJobs = [];

        if ($currentUser && ! empty($jobIds)) {
            $likedJobIds = IndustryJobPostLike::where('user_id', $currentUser->id)
                ->whereIn('industry_job_post_id', $jobIds)
                ->pluck('industry_job_post_id', 'industry_job_post_id')
                ->all();

            $savedJobIds = IndustryJobPostSave::where('user_id', $currentUser->id)
                ->whereIn('industry_job_post_id', $jobIds)
                ->pluck('industry_job_post_id', 'industry_job_post_id')
                ->all();

            $appliedJobs = IndustryJobApplication::where('applicant_id', $currentUser->id)
                ->whereIn('job_id', $jobIds)
                ->pluck('status', 'job_id')
                ->all();
        }

        $isOwnIndustry = (bool) ($currentUser && (int) $industry->created_by === (int) $currentUser->id);
        $industryInfo = [
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'logo' => $industry->logo,
            'is_own_industry' => $isOwnIndustry,
        ];

        $data = $paginated->getCollection()->map(function ($job) use ($likedJobIds, $savedJobIds, $appliedJobs, $industryInfo) {
            $isApplied = isset($appliedJobs[$job->id]);
            $applicationStatus = null;
            if ($isApplied) {
                $rawStatus = $appliedJobs[$job->id];
                $applicationStatus = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;
            }

            return [
                'id' => $job->id,
                'job_id' => $job->job_id,
                'slug' => $job->slug,
                'job_title' => $job->job_title,
                'work_mode' => $job->work_mode,
                'employment_type' => $job->employment_type,
                'network_type' => $job->network_type,
                'state' => $job->state ? [
                    'id' => $job->state->id,
                    'name' => $job->state->name,
                ] : null,
                'city' => $job->city ? [
                    'id' => $job->city->id,
                    'name' => $job->city->name,
                ] : null,
                'salary_min' => $job->salary_min !== null ? (string) $job->salary_min : null,
                'salary_max' => $job->salary_max !== null ? (string) $job->salary_max : null,
                'salary_type' => $job->salary_type,
                'applications_count' => (int) ($job->applications_count ?? 0),
                'views_count' => (int) ($job->views_count ?? 0),
                'job_description' => Str::limit(strip_tags((string) $job->job_description), 150),
                'is_liked' => isset($likedJobIds[$job->id]),
                'is_saved' => isset($savedJobIds[$job->id]),
                'is_applied' => $isApplied,
                'application_status' => $applicationStatus,
                'industry' => $industryInfo,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Industry job posts retrieved successfully.',
            'industry' => $industryInfo,
            'data' => $data,
            'total' => $totalJobs,
            'pagination' => [
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get cursor-paginated products/advertisements of a specific industry profile.
     */
    public function products(Request $request, string|int $identifier): JsonResponse
    {
        $industry = $this->findIndustry($identifier);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
            ], 404);
        }

        $currentUser = auth('api')->user() ?? auth()->user();

        // Auto-expire products whose boost or new release has expired
        IndustryProduct::where('industry_id', $industry->id)
            ->where('is_boosted', true)
            ->whereNotNull('boost_expires_at')
            ->where('boost_expires_at', '<=', now())
            ->update(['is_new_release' => false, 'is_boosted' => false]);

        IndustryProduct::where('industry_id', $industry->id)
            ->where('is_new_release', true)
            ->whereNotNull('new_release_expires_at')
            ->where('new_release_expires_at', '<=', now())
            ->update(['is_new_release' => false]);

        $query = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->where('status', 'active')
            ->with(['activeCampaign']);

        // Optional search and filters
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('product_id', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('network_type')) {
            $query->where('network_type', $request->input('network_type'));
        }

        if ($request->filled('industry_type')) {
            $query->where('industry_type', $request->input('industry_type'));
        }

        $perPage = min(max($request->integer('per_page', $request->integer('limit', 12)), 1), 100);
        $totalProducts = (clone $query)->count();

        // Boosted products appear first; non-boosted new products follow in newest-first order
        $paginated = $query->orderByDesc('is_boosted')
            ->orderByDesc('id')
            ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        // Batch load liked status for current user
        $productIds = $paginated->pluck('id')->all();
        $likedProductIds = [];
        if ($currentUser && ! empty($productIds)) {
            $likedProductIds = IndustryProductLike::where('user_id', $currentUser->id)
                ->whereIn('industry_product_id', $productIds)
                ->pluck('industry_product_id', 'industry_product_id')
                ->all();
        }

        $isOwnIndustry = (bool) ($currentUser && (int) $industry->created_by === (int) $currentUser->id);
        $industryInfo = [
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'logo' => $industry->logo,
            'is_own_industry' => $isOwnIndustry,
        ];

        $data = $paginated->getCollection()->map(function ($product) use ($likedProductIds, $industryInfo) {
            $imageUrl = null;
            if ($product->image) {
                $imageUrl = str_starts_with($product->image, 'http')
                    ? $product->image
                    : asset('storage/'.ltrim($product->image, '/'));
            }

            $isBoosted = (bool) $product->isBoosted();
            $isNewRelease = (bool) $product->isNewRelease();
            $adCampaignId = $isBoosted ? ($product->relationLoaded('activeCampaign') ? $product->activeCampaign?->id : $product->activeCampaign()->value('id')) : null;
            $badge = $product->getAdBadge();

            return [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'slug' => $product->slug,
                'views_count' => (int) ($product->views_count ?? 0),
                'likes_count' => (int) ($product->likes_count ?? 0),
                'is_liked' => isset($likedProductIds[$product->id]),
                'image_url' => $imageUrl,
                'network_type' => $product->network_type,
                'industry_type' => $product->industry_type,
                'description' => Str::limit(strip_tags((string) $product->description), 150),
                'is_boosted' => $isBoosted,
                'badge' => $badge,
                'ad_campaign_id' => $adCampaignId,
                'is_new_release' => $isNewRelease,
                'industry' => $industryInfo,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Industry products retrieved successfully.',
            'industry' => $industryInfo,
            'data' => $data,
            'total' => $totalProducts,
            'pagination' => [
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get cursor-paginated publications of a specific industry profile.
     */
    public function publications(Request $request, string|int $identifier): JsonResponse
    {
        $industry = $this->findIndustry($identifier);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
            ], 404);
        }

        $currentUser = auth('api')->user() ?? auth()->user();
        $isOwner = $currentUser && ((int) $industry->created_by === (int) $currentUser->id);

        $query = Publication::query()
            ->where('industry_id', $industry->id)
            ->where('status', 'active');

        // Optional search and filters
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('publication_id', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('publication_type')) {
            $query->where('publication_type', $request->input('publication_type'));
        }

        if ($request->filled('network_type')) {
            $query->where('network_type', $request->input('network_type'));
        }

        $perPage = min(max($request->integer('per_page', $request->integer('limit', 12)), 1), 100);
        $totalPublications = (clone $query)->count();

        $paginated = $query->with([
            'creator:id,first_name,last_name,username,profile_image',
            'industry:id,name,slug,logo,website,created_by',
        ])
            ->latest('id')
            ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        // Batch load liked status for current user
        $pubIds = $paginated->pluck('id')->all();
        $likedIds = [];
        if ($currentUser && ! empty($pubIds)) {
            $likedIds = PublicationLike::whereIn('publication_id', $pubIds)
                ->where('user_id', $currentUser->id)
                ->pluck('publication_id', 'publication_id')
                ->all();
        }

        $data = $paginated->getCollection()->map(function ($pub) use ($likedIds) {
            return (new PublicationResource($pub, $likedIds))->resolve();
        });

        return response()->json([
            'success' => true,
            'message' => 'Industry publications retrieved successfully.',
            'industry' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
            ],
            'data' => $data,
            'total' => $totalPublications,
            'pagination' => [
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get cursor-paginated related industries for the visited industry profile,
     * matching the visited industry's industry category, ordered randomly,
     * and excluding any industries the current user already follows.
     */
    public function relatedIndustries(Request $request, string|int $identifier): JsonResponse
    {
        $industry = $this->findIndustry($identifier);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
            ], 404);
        }

        $currentUser = auth('api')->user() ?? auth()->user();

        $query = Industry::query()
            ->where('id', '!=', $industry->id);

        if (! empty($industry->industry)) {
            $query->where('industry', $industry->industry);
        }

        if ($currentUser) {
            $query->whereDoesntHave('follows', function ($q) use ($currentUser) {
                $q->where('user_id', $currentUser->id);
            });
        }

        // Optional search by name or tagline
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('tagline', 'LIKE', "%{$search}%");
            });
        }

        $cursor = null;
        if ($request->filled('cursor')) {
            try {
                $cursor = Cursor::fromEncoded($request->input('cursor'));
            } catch (\Throwable) {
                $cursor = null;
            }
        }

        $seed = $cursor ? (int) ($cursor->parameter('seed') ?? 0) : 0;
        if ($seed <= 0) {
            $seed = $request->integer('seed', 0);
            if ($seed <= 0) {
                $seed = random_int(100000, 999999);
            }
        }

        $offset = $cursor ? (int) ($cursor->parameter('offset') ?? 0) : 0;
        $offset = max(0, $offset);
        $perPage = min(max($request->integer('per_page', $request->integer('limit', 10)), 1), 100);

        $allIds = (clone $query)->pluck('id')->all();
        $total = count($allIds);

        // Deterministic pseudo-random sorting based on seed
        usort($allIds, function ($a, $b) use ($seed) {
            return strcmp(md5($seed.'_'.$a), md5($seed.'_'.$b));
        });

        $slice = array_slice($allIds, $offset, $perPage);
        $hasMorePages = ($offset + count($slice)) < $total;

        $nextCursor = $hasMorePages
            ? (new Cursor(['offset' => $offset + count($slice), 'seed' => $seed]))->encode()
            : null;

        $prevCursor = $offset > 0
            ? (new Cursor(['offset' => max(0, $offset - $perPage), 'seed' => $seed]))->encode()
            : null;

        $industries = ! empty($slice)
            ? Industry::whereIn('id', $slice)
                ->withCount('followers')
                ->get()
                ->keyBy('id')
            : collect();

        $data = collect($slice)
            ->map(fn ($id) => $industries->get($id))
            ->filter()
            ->values()
            ->map(function (Industry $ind) {
                return [
                    'id' => $ind->id,
                    'name' => $ind->name,
                    'slug' => $ind->slug,
                    'industry' => $ind->industry,
                    'tagline' => $ind->tagline,
                    'address' => $ind->address,
                    'website' => $ind->website,
                    'company_size' => $ind->company_size,
                    'logo' => $ind->logo,
                    'cover_image' => $ind->cover_image,
                    'followers_count' => (int) ($ind->followers_count ?? 0),
                    'is_following' => false,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Related industries retrieved successfully.',
            'industry' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'industry' => $industry->industry,
            ],
            'data' => $data,
            'total' => $total,
            'pagination' => [
                'per_page' => $perPage,
                'next_cursor' => $nextCursor,
                'prev_cursor' => $prevCursor,
                'has_more_pages' => $hasMorePages,
            ],
        ]);
    }

    /**
     * Resolve industry model by ID or slug.
     */
    private function findIndustry(string|int $identifier): ?Industry
    {
        if (is_numeric($identifier)) {
            return Industry::find((int) $identifier);
        }

        return Industry::where('slug', (string) $identifier)->first();
    }
}
