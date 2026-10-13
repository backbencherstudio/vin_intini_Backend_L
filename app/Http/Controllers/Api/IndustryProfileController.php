<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryJobPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryJobPostResource;
use App\Http\Resources\IndustryProductResource;
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

        $currentUser = auth('api')->user() ?? auth()->user();
        $isOwner = $currentUser && ((int) $industry->created_by === (int) $currentUser->id);

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
            'industry:id,name,slug,logo,website,created_by',
            'state:id,name',
            'city:id,name',
            'creator:id,first_name,last_name,username,profile_image',
        ])
            ->withCount('applications')
            ->latest('id')
            ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        // Batch load user interactions (likes, saves, applications) to eliminate N+1
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

        $data = $paginated->getCollection()->map(function ($job) use ($likedJobIds, $savedJobIds, $appliedJobs, $currentUser) {
            return (new IndustryJobPostResource(
                $job,
                $likedJobIds,
                $savedJobIds,
                $appliedJobs,
                $currentUser
            ))->resolve();
        });

        return response()->json([
            'success' => true,
            'message' => 'Industry job posts retrieved successfully.',
            'industry' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
            ],
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
        $isOwner = $currentUser && ((int) $industry->created_by === (int) $currentUser->id);

        $query = IndustryProduct::query()
            ->where('industry_id', $industry->id)
            ->where('status', 'active');

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

        $paginated = $query->with([
            'creator:id,first_name,last_name,username,profile_image',
            'industry:id,name,slug,logo,website,created_by',
            'section:id,name,network_type,industry_type',
            'category:id,section_id,category_name',
        ])
            ->latest('id')
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

        $data = $paginated->getCollection()->map(function ($product) use ($likedProductIds) {
            return (new IndustryProductResource($product, $likedProductIds))->resolve();
        });

        return response()->json([
            'success' => true,
            'message' => 'Industry products retrieved successfully.',
            'industry' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
            ],
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
