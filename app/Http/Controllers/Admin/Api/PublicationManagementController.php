<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
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

        // Global KPI summary cards for admin
        $statistics = [
            'total_publications' => Publication::count(),
            'active_publications' => Publication::where('status', 'active')->count(),
            'inactive_publications' => Publication::where('status', 'inactive')->count(),
            'draft_publications' => Publication::where('status', 'draft')->count(),
        ];

        $query = Publication::query()
            ->with(['creator', 'industry'])
            ->latest('id');

        // Search filter (title, ID, slug, abstract, author, creator, industry)
        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('publication_id', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('website_url', 'like', "%{$search}%")
                    ->orWhereHas('creator', function (Builder $creator) use ($search) {
                        $creator->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('industry', function (Builder $industry) use ($search) {
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

        $rows = $paginated->getCollection()->map(function (Publication $pub, int $index) use ($paginated, $formatLogoUrl) {
            $slNo = (($paginated->currentPage() - 1) * $paginated->perPage()) + $index + 1;
            $creator = $pub->creator;
            $industry = $pub->industry;

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
                'created_at' => $pub->created_at?->format('d M Y'),
                'created_at_iso' => $pub->created_at?->toIso8601String(),
                'deleted_at' => $pub->deleted_at?->toIso8601String(),
                'is_deleted' => (bool) $pub->trashed(),
                'creator' => $creator ? [
                    'id' => $creator->id,
                    'name' => trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) ?: ($creator->username ?? 'User'),
                    'email' => $creator->email,
                    'username' => $creator->username,
                    'profile_image_url' => $creator->profile_image_url,
                    'tier' => $creator->tier,
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
     * Delete publication (supports soft-delete by default, and permanent delete via ?force=true).
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $publication = Publication::withTrashed()
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', (string) $id);
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
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', (string) $id);
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
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', (string) $id);
                }
            })
            ->firstOrFail();
    }
}
