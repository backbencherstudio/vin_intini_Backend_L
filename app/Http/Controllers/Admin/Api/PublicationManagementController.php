<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\AdCampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationManagementController extends Controller
{
    /**
     * List all publications for admin management with filters, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $networkType = $request->input('network_type', 'all');
        $publicationType = $request->input('publication_type', 'all');
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        // Normalize network type aliases (e.g. 'neuro' -> 'neuroscience', 'psych' -> 'psychology')
        if ($networkType !== 'all') {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $networkType));
            if (str_contains($rawNet, 'neuro')) {
                $networkType = 'neuroscience';
            } elseif (str_contains($rawNet, 'psych')) {
                $networkType = 'psychology';
            }
        }

        // Global KPI summary cards for admin scoped by network if specified
        $baseScope = Publication::query();
        if ($networkType !== 'all' && in_array($networkType, ['psychology', 'neuroscience'], true)) {
            $baseScope->where('network_type', $networkType);
        }

        $statistics = [
            'total_publications' => (clone $baseScope)->count(),
            'active_publications' => (clone $baseScope)->where('status', 'active')->count(),
            'industry_publications' => (clone $baseScope)->whereNotNull('industry_id')->count(),
            'premium_user_publications' => (clone $baseScope)->whereNull('industry_id')->where(function ($q) {
                $q->whereDoesntHave('creator', function ($cq) {
                    $cq->role('admin');
                });
            })->count(),
            'admin_publications' => (clone $baseScope)->whereNull('industry_id')->where(function ($q) {
                $q->whereHas('creator', function ($cq) {
                    $cq->role('admin');
                })->orWhereNull('creator_id');
            })->count(),
            'boosted_publications' => (clone $baseScope)->where(function (Builder $b): void {
                $b->where('is_boosted', true)
                  ->orWhere(function (Builder $sub): void {
                      $sub->whereNotNull('boost_expires_at')->where('boost_expires_at', '>', now());
                  });
            })->count(),
            'inactive_publications' => (clone $baseScope)->where('status', 'inactive')->count(),
            'draft_publications' => (clone $baseScope)->where('status', 'draft')->count(),
        ];

        $query = Publication::query()
            ->with(['creator', 'industry'])
            ->latest('id');

        // Search filter (title, ID, slug, abstract, author, creator, industry)
        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('publication_id', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('website_url', 'like', "%{$search}%")
                    ->orWhereHas('creator', function (Builder $creator) use ($search): void {
                        $creator->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('industry', function (Builder $industry) use ($search): void {
                        $industry->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status !== 'all' && in_array($status, ['active', 'inactive', 'draft'], true)) {
            $query->where('status', $status);
        }

        // Network filter ('psychology', 'neuroscience')
        if ($networkType !== 'all' && in_array($networkType, ['psychology', 'neuroscience'], true)) {
            $query->where('network_type', $networkType);
        }

        // Publication type filter ('professional', 'university', 'freelance', 'other')
        if ($publicationType !== 'all') {
            $query->where('publication_type', $publicationType);
        }

        $paginated = $query->paginate($perPage)->withQueryString();

        $formatLogoUrl = function (?string $logo): ?string {
            if (! $logo) {
                return null;
            }

            return str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'));
        };

        $rows = $paginated->getCollection()->map(function (Publication $pub, int $index) use ($paginated, $formatLogoUrl): array {
            $slNo = (($paginated->currentPage() - 1) * $paginated->perPage()) + $index + 1;
            $creator = $pub->creator;
            $industry = $pub->industry;

            $isAdminCreator = $creator ? ($creator->hasRole('admin') || in_array($creator->email, ['admin@gmail.com'], true)) : false;
            $isAdminPublication = empty($pub->industry_id) && ($isAdminCreator || empty($pub->creator_id));

            return [
                'sl_no' => $slNo,
                'id' => $pub->id,
                'publication_id' => $pub->publication_id,
                'title' => $pub->title,
                'slug' => $pub->slug,
                'network_type' => $pub->network_type,
                'publication_type' => $pub->publication_type,
                'status' => $pub->status,
                'authors' => $pub->authors ?? [],
                'short_abstract' => Str::limit(strip_tags((string) $pub->abstract), 140),
                'website_url' => $pub->website_url,
                'attachment_url' => $pub->attachment_url,
                'file_extension' => $pub->file_extension,
                'information_confirmed' => (bool) $pub->information_confirmed,
                'views_count' => (int) $pub->views_count,
                'likes_count' => (int) $pub->likes_count,
                'is_boosted' => (bool) $pub->is_boosted,
                'is_sponsored' => (bool) ($pub->is_boosted && $pub->boost_expires_at && $pub->boost_expires_at->isFuture()),
                'boost_expires_at' => $pub->boost_expires_at?->toIso8601String(),
                'created_at' => $pub->created_at?->format('d M Y'),
                'created_at_iso' => $pub->created_at?->toIso8601String(),
                'deleted_at' => $pub->deleted_at?->toIso8601String(),
                'is_deleted' => (bool) $pub->trashed(),
                'industry_id' => $pub->industry_id,
                'creator_id' => $pub->creator_id,
                'is_admin_publication' => $isAdminPublication,
                'creator' => $creator ? [
                    'id' => $creator->id,
                    'name' => trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) ?: ($creator->username ?? 'User'),
                    'email' => $creator->email,
                    'username' => $creator->username,
                    'profile_image_url' => $creator->profile_image_url,
                    'tier' => $creator->tier,
                    'is_admin' => $isAdminCreator,
                    'roles' => $creator->getRoleNames()->toArray(),
                ] : null,
                'industry' => $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'logo_url' => $formatLogoUrl($industry->logo),
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'overview' => $statistics,
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ], 200);
    }

    /**
     * Store a new publication directly from Admin Panel.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'network_type' => ['required', 'string', 'in:psychology,neuroscience'],
            'publication_type' => ['required', 'string', 'in:professional,university,freelance,other'],
            'abstract' => ['required', 'string', 'max:10000'],
            'website_url' => ['required', 'string', 'url', 'max:1000'],
            'authors' => ['nullable'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:25600'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'owner_type' => ['nullable', 'string', 'in:industry,premium_user,official'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('publications/attachments', 'public');
        }

        $authors = $this->formatAuthors($request->input('authors'));

        $baseSlug = Str::slug($validated['title']);
        $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(6));

        $adminUser = auth('api')->user();

        // Determine owner (Industry vs Premium User vs Official MindUnite)
        $ownerType = $validated['owner_type'] ?? null;
        $industryId = null;
        $creatorId = $adminUser?->id;

        if ($ownerType === 'industry' || (!empty($validated['industry_id']) && $ownerType !== 'premium_user')) {
            $industryId = (int) $validated['industry_id'];
            $industry = \App\Models\Industry::find($industryId);
            $creatorId = $industry?->user_id ?? $adminUser?->id;
        } elseif ($ownerType === 'premium_user' || (!empty($validated['creator_id']) && empty($validated['industry_id']))) {
            $creatorId = (int) $validated['creator_id'];
            $industryId = null;
        } else {
            $creatorId = $adminUser?->id;
            $industryId = null;
        }

        $newReleaseHours = app(\App\Services\Advertising\BoostSettingService::class)->getNewReleaseHours();
        $isNewRelease = ($validated['status'] ?? 'active') === 'active' && $newReleaseHours > 0;
        $newReleaseExpiresAt = $isNewRelease ? now()->addHours($newReleaseHours) : null;

        $publication = Publication::create([
            'creator_id' => $creatorId,
            'industry_id' => $industryId,
            'network_type' => $validated['network_type'],
            'publication_type' => $validated['publication_type'],
            'title' => $validated['title'],
            'slug' => $uniqueSlug,
            'authors' => $authors,
            'abstract' => $validated['abstract'],
            'website_url' => $validated['website_url'],
            'attachment' => $attachmentPath,
            'information_confirmed' => true,
            'status' => $validated['status'] ?? 'active',
            'is_new_release' => $isNewRelease,
            'new_release_expires_at' => $newReleaseExpiresAt,
            'is_boosted' => false,
            'boosted_at' => null,
            'boost_expires_at' => null,
            'boost_score' => 0,
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $publication->load(['creator', 'industry']);

        return response()->json([
            'success' => true,
            'message' => 'Publication created successfully.',
            'data' => (new \App\Http\Resources\PublicationResource($publication))->resolve(),
        ], 201);
    }

    /**
     * Update an existing publication from Admin Panel.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'network_type' => ['sometimes', 'required', 'string', 'in:psychology,neuroscience'],
            'publication_type' => ['sometimes', 'required', 'string', 'in:professional,university,freelance,other'],
            'abstract' => ['sometimes', 'required', 'string', 'max:10000'],
            'website_url' => ['sometimes', 'required', 'string', 'url', 'max:1000'],
            'authors' => ['nullable'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:25600'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,draft'],
            'owner_type' => ['nullable', 'string', 'in:industry,premium_user,official'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_boosted' => ['nullable', 'boolean'],
            'boost_score' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        if ($request->hasFile('attachment')) {
            if ($publication->attachment && Storage::disk('public')->exists($publication->attachment)) {
                Storage::disk('public')->delete($publication->attachment);
            }
            $publication->attachment = $request->file('attachment')->store('publications/attachments', 'public');
        }

        if (isset($validated['title']) && $validated['title'] !== $publication->title) {
            $baseSlug = Str::slug($validated['title']);
            $publication->slug = $baseSlug . '-' . Str::lower(Str::random(6));
            $publication->title = $validated['title'];
        }

        if (array_key_exists('authors', $validated)) {
            $publication->authors = $this->formatAuthors($validated['authors']);
        }

        foreach (['network_type', 'publication_type', 'abstract', 'website_url', 'status'] as $field) {
            if (isset($validated[$field])) {
                $publication->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('owner_type', $validated) || array_key_exists('industry_id', $validated) || array_key_exists('creator_id', $validated)) {
            $ownerType = $validated['owner_type'] ?? null;
            if ($ownerType === 'industry' || (!empty($validated['industry_id']) && $ownerType !== 'premium_user')) {
                $publication->industry_id = (int) $validated['industry_id'];
                $industry = \App\Models\Industry::find($publication->industry_id);
                if ($industry?->user_id) {
                    $publication->creator_id = $industry->user_id;
                }
            } elseif ($ownerType === 'premium_user' || (!empty($validated['creator_id']) && empty($validated['industry_id']))) {
                $publication->creator_id = (int) $validated['creator_id'];
                $publication->industry_id = null;
            } elseif ($ownerType === 'official') {
                $adminUser = auth('api')->user();
                $publication->creator_id = $adminUser?->id;
                $publication->industry_id = null;
            }
        }

        if (array_key_exists('is_boosted', $validated)) {
            $publication->is_boosted = (bool) $validated['is_boosted'];
            if ($publication->is_boosted && !$publication->boost_expires_at) {
                $publication->boosted_at = now();
                $publication->boost_expires_at = now()->addDays(30);
            } elseif (!$publication->is_boosted) {
                $publication->boost_expires_at = null;
            }
        }

        if (isset($validated['boost_score'])) {
            $publication->boost_score = (int) $validated['boost_score'];
        }

        $publication->save();
        $publication->load(['creator', 'industry']);

        return response()->json([
            'success' => true,
            'message' => 'Publication updated successfully.',
            'data' => (new \App\Http\Resources\PublicationResource($publication))->resolve(),
        ], 200);
    }

    /**
     * Format authors array or string.
     */
    protected function formatAuthors(mixed $authors): ?array
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

    /**
     * Show detailed view of a single publication including creator, industry, and engagement data.
     */
    public function show(int|string $id): JsonResponse
    {
        $publication = $this->findPublicationWithRelations($id);

        $formatLogoUrl = function (?string $logo): ?string {
            if (! $logo) {
                return null;
            }

            return str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'));
        };

        $creator = $publication->creator;
        $industry = $publication->industry;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $publication->id,
                'publication_id' => $publication->publication_id,
                'title' => $publication->title,
                'slug' => $publication->slug,
                'network_type' => $publication->network_type,
                'publication_type' => $publication->publication_type,
                'status' => $publication->status,
                'authors' => $publication->authors ?? [],
                'abstract' => $publication->abstract,
                'website_url' => $publication->website_url,
                'attachment' => $publication->attachment,
                'attachment_url' => $publication->attachment_url,
                'file_extension' => $publication->file_extension,
                'information_confirmed' => (bool) $publication->information_confirmed,
                'views_count' => (int) $publication->views_count,
                'likes_count' => (int) $publication->likes_count,
                'created_at' => $publication->created_at?->format('d M Y, h:i A'),
                'created_at_iso' => $publication->created_at?->toIso8601String(),
                'updated_at' => $publication->updated_at?->format('d M Y, h:i A'),
                'updated_at_iso' => $publication->updated_at?->toIso8601String(),
                'deleted_at' => $publication->deleted_at?->toIso8601String(),
                'is_deleted' => (bool) $publication->trashed(),
                'creator' => $creator ? [
                    'id' => $creator->id,
                    'first_name' => $creator->first_name,
                    'last_name' => $creator->last_name,
                    'full_name' => trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) ?: ($creator->username ?? 'User'),
                    'username' => $creator->username,
                    'email' => $creator->email,
                    'mobile' => $creator->mobile,
                    'title' => $creator->title,
                    'profile_image_url' => $creator->profile_image_url,
                    'tier' => $creator->tier,
                    'is_verified' => (bool) $creator->is_verified,
                    'is_suspended' => (bool) $creator->is_suspended,
                    'status' => $creator->status,
                ] : null,
                'industry' => $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'industry' => $industry->industry,
                    'logo_url' => $formatLogoUrl($industry->logo),
                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,
                    'views_count' => (int) $industry->views_count,
                ] : null,
            ],
        ], 200);
    }

    /**
     * Toggle publication status between active and inactive.
     */
    public function toggleStatus(int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);
        $publication->status = ($publication->status === 'active') ? 'inactive' : 'active';
        $publication->save();

        return response()->json([
            'success' => true,
            'message' => "Publication status toggled to '{$publication->status}' successfully.",
            'data' => [
                'id' => $publication->id,
                'publication_id' => $publication->publication_id,
                'status' => $publication->status,
                'updated_at' => $publication->updated_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Toggle boost status for publication.
     */
    public function toggleBoost(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);
        $days = (int) $request->input('days', 30);
        if ($days <= 0) {
            $days = 30;
        }

        if ($publication->is_boosted) {
            $publication->is_boosted = false;
            $publication->boost_expires_at = null;
            $publication->boost_score = 0;

            AdCampaign::where('boostable_type', Publication::class)
                ->where('boostable_id', $publication->id)
                ->whereIn('status', [AdCampaignStatus::ACTIVE, AdCampaignStatus::PAUSED])
                ->update(['status' => AdCampaignStatus::CANCELLED]);
        } else {
            $publication->is_boosted = true;
            $publication->boosted_at = now();
            $publication->boost_expires_at = now()->addDays($days);
            if (! $publication->boost_score) {
                $publication->boost_score = 100;
            }

            AdCampaign::create([
                'boostable_type' => Publication::class,
                'boostable_id' => $publication->id,
                'user_id' => $publication->creator_id ?? auth('api')->id(),
                'industry_id' => $publication->industry_id,
                'ad_type' => 'publication',
                'network_type' => $publication->network_type ?? 'psychology',
                'placements' => ['publication_feed', 'newsfeed'],
                'amount_paid' => 0.00,
                'currency' => 'USD',
                'payment_platform' => 'admin_complimentary',
                'duration_days' => $days,
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
                'status' => AdCampaignStatus::ACTIVE,
                'total_impressions' => 0,
                'total_clicks' => 0,
            ]);
        }

        $publication->save();

        return response()->json([
            'success' => true,
            'message' => $publication->is_boosted ? "Publication boosted successfully for {$days} days." : 'Publication boost deactivated.',
            'data' => [
                'id' => $publication->id,
                'publication_id' => $publication->publication_id,
                'is_boosted' => (bool) $publication->is_boosted,
                'boosted_at' => $publication->boosted_at?->toIso8601String(),
                'boost_expires_at' => $publication->boost_expires_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Delete publication (supports soft-delete by default, and permanent delete via ?force=true).
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $publication = Publication::withTrashed()
            ->where(function ($q) use ($id): void {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', $id);
                }
            })
            ->firstOrFail();

        $force = $request->boolean('force') || $request->input('force') === 'true';

        if ($force) {
            if ($publication->attachment && Storage::disk('public')->exists($publication->attachment)) {
                Storage::disk('public')->delete($publication->attachment);
            }
            $publication->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Publication permanently deleted successfully.',
            ], 200);
        }

        $publication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Publication soft-deleted successfully.',
        ], 200);
    }

    /**
     * Locate publication by primary id or scoped publication_id.
     */
    protected function findPublication(int|string $id): Publication
    {
        return Publication::query()
            ->where(function ($q) use ($id): void {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', $id);
                }
            })
            ->firstOrFail();
    }

    /**
     * Locate publication with relationships and soft-deleted support.
     */
    protected function findPublicationWithRelations(int|string $id): Publication
    {
        return Publication::withTrashed()
            ->with(['creator', 'industry'])
            ->where(function ($q) use ($id): void {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', $id);
                }
            })
            ->firstOrFail();
    }
}
