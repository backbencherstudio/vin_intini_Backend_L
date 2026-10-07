<?php

namespace App\Http\Controllers\Api;

use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Resources\PublicationResource;
use App\Models\Publication;
use App\Models\PublicationLike;
use App\Models\PublicationView;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    /**
     * Public Publications Feed.
     * Supports filtering by network_type, tab (professional vs peer_reviewed),
     * publication_type, keyword search, and pagination.
     */
    public function feed(Request $request): JsonResponse
    {
        if ($request->filled('network_type')) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $request->input('network_type')));
            if (str_contains($rawNet, 'neuro')) {
                $request->merge(['network_type' => 'neuroscience']);
            } elseif (str_contains($rawNet, 'psych')) {
                $request->merge(['network_type' => 'psychology']);
            }
        }

        $request->validate([
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'publication_type' => ['nullable', 'string', 'in:professional,university,freelance,other'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Publication::query()
            ->where('status', 'active')
            ->where('network_type', $request->input('network_type'))
            ->with(['creator', 'industry'])
            ->latest('id');

        // Filter by the 4 UI tabs: professional, university, freelance, other
        if ($request->filled('publication_type')) {
            $query->where('publication_type', $request->input('publication_type'));
        }

        $perPage = min(max($request->integer('per_page', 12), 1), 100);
        $paginated = $query->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        // Batch load likes for authenticated user to prevent N+1
        $likedIds = [];
        if (auth('api')->check()) {
            $pubIds = $paginated->pluck('id')->all();
            $likedIds = PublicationLike::whereIn('publication_id', $pubIds)
                ->where('user_id', auth('api')->id())
                ->pluck('publication_id', 'publication_id')
                ->all();
        }

        return response()->json([
            'success' => true,
            'message' => 'Publications retrieved successfully.',
            'data' => PublicationResource::collection($paginated->items())->map(function ($res) use ($likedIds) {
                return (new PublicationResource($res->resource, $likedIds))->resolve();
            }),
            'pagination' => [
                'per_page' => $paginated->perPage(),
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Show single publication details and record unique view.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id, ['creator', 'industry']);

        $user = auth('api')->user();
        $this->recordView($publication, $user, $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Publication details retrieved successfully.',
            'data' => (new PublicationResource($publication->fresh(['creator', 'industry'])))->resolve(),
        ]);
    }

    /**
     * Toggle like on publication.
     */
    public function toggleLike(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $publication = $this->findPublication($id);

        return DB::transaction(function () use ($publication, $user, $request) {
            $like = PublicationLike::where('publication_id', $publication->id)
                ->where('user_id', $user->id)
                ->first();

            if ($like) {
                $like->delete();
                $publication->decrement('likes_count');
                $isLiked = false;
                $message = 'Publication unliked successfully.';
            } else {
                PublicationLike::create([
                    'publication_id' => $publication->id,
                    'user_id' => $user->id,
                ]);
                $publication->increment('likes_count');
                $isLiked = true;
                $message = 'Publication liked successfully.';

                // Automatically count view if user has not yet viewed
                $this->recordView($publication, $user, $request->ip());
            }

            $freshPub = $publication->fresh();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'publication_id' => $publication->id,
                    'is_liked' => $isLiked,
                    'likes_count' => (int) $freshPub->likes_count,
                    'views_count' => (int) $freshPub->views_count,
                ],
            ]);
        });
    }

    /**
     * Create a new publication.
     */
    public function store(StorePublicationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $validated = $request->validated();

        $hasIndustryPlan = $user->hasActiveSubscription(PlanType::INDUSTRY);

        // Determine ownership and publication type
        if ($hasIndustryPlan && $user->industry) {
            $industryId = $user->industry->id;
            $publicationType = 'professional';
        } else {
            // Premium individual researcher
            $industryId = null;
            $publicationType = $validated['publication_type'];
        }

        // Handle attachment upload (PDF/Document)
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('publications/attachments', 'public');
        }

        // Format authors array
        $authors = $this->formatAuthors($request->input('authors'));

        // Generate clean unique slug
        $baseSlug = Str::slug($validated['title']);
        $uniqueSlug = $baseSlug.'-'.Str::lower(Str::random(6));

        $publication = Publication::create([
            'creator_id' => $user->id,
            'industry_id' => $industryId,
            'network_type' => $validated['network_type'],
            'publication_type' => $publicationType,
            'title' => $validated['title'],
            'slug' => $uniqueSlug,
            'authors' => $authors,
            'abstract' => $validated['abstract'],
            'website_url' => $validated['website_url'],
            'attachment' => $attachmentPath,
            'information_confirmed' => (bool) $request->boolean('information_confirmed', true),
            'status' => $validated['status'] ?? 'active',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $publication->load(['creator', 'industry']);

        return response()->json([
            'success' => true,
            'message' => 'Publication submitted successfully.',
            'data' => (new PublicationResource($publication))->resolve(),
        ], 201);
    }

    /**
     * My Listings: List publications owned by the current user or their industry.
     */
    public function myListings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $query = Publication::query()->with(['creator', 'industry'])->latest('id');

        // If user belongs to an Industry, load industry publications
        if ($user->industry && $user->hasActiveSubscription(PlanType::INDUSTRY)) {
            $query->where('industry_id', $user->industry->id);
        } else {
            // Individual user publications
            $query->where('creator_id', $user->id)->whereNull('industry_id');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('publication_id', 'LIKE', "%{$search}%")
                    ->orWhere('abstract', 'LIKE', "%{$search}%");
            });
        }

        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'My publications retrieved successfully.',
            'data' => PublicationResource::collection($paginated->items())->map(fn ($r) => (new PublicationResource($r->resource))->resolve()),
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
     * Get single publication data for editing.
     */
    public function editData(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $publication = $this->findPublication($id, ['creator', 'industry']);

        if (! $this->authorizePublicationAction($user, $publication)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view or edit this publication.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => (new PublicationResource($publication))->resolve(),
        ]);
    }

    /**
     * Update an existing publication.
     */
    public function update(UpdatePublicationRequest $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $publication = $this->findPublication($id);

        if (! $this->authorizePublicationAction($user, $publication)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this publication.',
            ], 403);
        }

        $validated = $request->validated();

        // Handle replacement of attachment file
        if ($request->hasFile('attachment')) {
            if ($publication->attachment && Storage::disk('public')->exists($publication->attachment)) {
                Storage::disk('public')->delete($publication->attachment);
            }
            $publication->attachment = $request->file('attachment')->store('publications/attachments', 'public');
        }

        // Re-generate slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $publication->title) {
            $baseSlug = Str::slug($validated['title']);
            $publication->slug = $baseSlug.'-'.Str::lower(Str::random(6));
            $publication->title = $validated['title'];
        }

        if (array_key_exists('authors', $validated)) {
            $publication->authors = $this->formatAuthors($validated['authors']);
        }

        foreach (['network_type', 'abstract', 'website_url', 'status'] as $field) {
            if (isset($validated[$field])) {
                $publication->{$field} = $validated[$field];
            }
        }

        if (isset($validated['publication_type'])) {
            if ($publication->industry_id || ($user->hasActiveSubscription(PlanType::INDUSTRY) && $user->industry)) {
                $publication->publication_type = 'professional';
            } else {
                $publication->publication_type = $validated['publication_type'];
            }
        }

        $publication->save();
        $publication->load(['creator', 'industry']);

        return response()->json([
            'success' => true,
            'message' => 'Publication updated successfully.',
            'data' => (new PublicationResource($publication))->resolve(),
        ]);
    }

    /**
     * Quick status update.
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,draft'],
        ]);

        $publication = $this->findPublication($id);

        if (! $this->authorizePublicationAction($user, $publication)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this publication status.',
            ], 403);
        }

        $publication->status = $request->input('status');
        $publication->save();

        return response()->json([
            'success' => true,
            'message' => 'Publication status updated successfully.',
            'data' => [
                'id' => $publication->id,
                'status' => $publication->status,
            ],
        ]);
    }

    /**
     * Soft delete a publication.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $publication = $this->findPublication($id);

        if (! $this->authorizePublicationAction($user, $publication)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this publication.',
            ], 403);
        }

        $publication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Publication removed successfully.',
        ]);
    }

    /**
     * Find publication by id, publication_id, or slug.
     */
    private function findPublication(int|string $id, array $relations = []): Publication
    {
        $query = Publication::query()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                    ->orWhere('publication_id', (string) $id)
                    ->orWhere('slug', (string) $id);
            });

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->firstOrFail();
    }

    /**
     * Authorize user action on publication:
     * 1. System admin can perform any action.
     * 2. Creator can perform action if matching.
     * 3. For company publications, member of the industry can perform action.
     */
    private function authorizePublicationAction(User $user, Publication $publication): bool
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return true;
        }

        if ($publication->creator_id && $publication->creator_id === $user->id) {
            return true;
        }

        if ($publication->industry_id && $user->industry && $user->industry->id === $publication->industry_id) {
            return true;
        }

        return false;
    }

    /**
     * Unique view recording: exactly 1 view per user / IP.
     */
    private function recordView(Publication $publication, ?User $user, ?string $ip): void
    {
        $viewCriteria = [
            'publication_id' => $publication->id,
            'user_id' => $user?->id,
        ];

        if (! $user) {
            $viewCriteria['ip_address'] = $ip;
        }

        $view = PublicationView::firstOrCreate(
            $viewCriteria,
            ['ip_address' => $ip]
        );

        if ($view->wasRecentlyCreated) {
            $publication->increment('views_count');
        }
    }

    /**
     * Format authors array or string.
     */
    private function formatAuthors(mixed $authors): ?array
    {
        if (empty($authors)) {
            return null;
        }

        if (is_string($authors)) {
            $decoded = json_decode($authors, true);
            if (is_array($decoded)) {
                $authors = $decoded;
            } else {
                $authors = explode(',', $authors);
            }
        }

        if (is_array($authors)) {
            return array_values(array_filter(array_map('trim', $authors)));
        }

        return null;
    }
}
