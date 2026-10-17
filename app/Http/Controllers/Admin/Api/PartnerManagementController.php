<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerManagementController extends Controller
{
    /**
     * List all partner industries for admin management with filters, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = strtolower(trim((string) $request->input('status', 'all')));
        $industryType = trim((string) ($request->input('industry') ?? $request->input('industry_type', 'all')));
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        // Summary Statistics for top KPI cards
        $summary = [
            'total_partners' => Industry::count(),
            'active_partners' => Industry::where(function (Builder $q) {
                $q->where('status', 'active')->orWhereNull('status');
            })->count(),
            'paused_partners' => Industry::where('status', 'paused')->count(),
        ];

        $query = Industry::query()
            ->with(['creator.profile.state'])
            ->latest('id');

        // Search Filter: Company Name, Tagline, Description, or Owner details
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhereHas('creator', function (Builder $userQ) use ($search) {
                        $userQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter: active, paused
        if ($status !== 'all' && in_array($status, ['active', 'paused'], true)) {
            if ($status === 'active') {
                $query->where(function (Builder $q) {
                    $q->where('status', 'active')->orWhereNull('status');
                });
            } else {
                $query->where('status', 'paused');
            }
        }

        // Industry Type Filter: Biotechnology, Psychotropics, Publications, etc.
        if ($industryType !== 'all' && $industryType !== '') {
            $query->where('industry', 'like', "%{$industryType}%");
        }

        $paginated = $query->paginate($perPage)->withQueryString();

        $data = $paginated->getCollection()->map(function (Industry $industry, int $index) use ($paginated) {
            $slNo = (($paginated->currentPage() - 1) * $paginated->perPage()) + $index + 1;

            return $this->formatPartnerRow($industry, $slNo);
        });

        return response()->json([
            'success' => true,
            'message' => 'Partner industries retrieved successfully.',
            'summary' => $summary,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Toggle pause status between active and paused.
     */
    public function togglePause(Request $request, int|string $id): JsonResponse
    {
        $industry = $this->findIndustry($id);

        if ($request->has('is_paused')) {
            $isPaused = filter_var($request->input('is_paused'), FILTER_VALIDATE_BOOLEAN);
            $industry->status = $isPaused ? 'paused' : 'active';
        } elseif ($request->filled('status')) {
            $industry->status = ($request->input('status') === 'paused') ? 'paused' : 'active';
        } else {
            $industry->status = ($industry->status === 'paused') ? 'active' : 'paused';
        }

        $industry->paused_at = ($industry->status === 'paused') ? now() : null;
        $industry->save();

        $actionText = ($industry->status === 'paused') ? 'paused' : 'resumed';

        return response()->json([
            'success' => true,
            'message' => "Industry '{$industry->name}' has been {$actionText} successfully.",
            'data' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'status' => $industry->status,
                'is_paused' => $industry->status === 'paused',
                'paused_at' => $industry->paused_at?->toIso8601String(),
                'updated_at' => $industry->updated_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Format a partner industry row for API lists.
     */
    protected function formatPartnerRow(Industry $industry, int $slNo): array
    {
        $creator = $industry->creator;
        $state = $creator?->profile?->state?->name;
        $country = $creator?->profile?->country;
        $location = trim(implode(', ', array_filter([$state, $country]))) ?: null;

        $creatorName = $creator ? trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) : null;
        $creatorName = $creatorName ?: ($creator?->username ?? 'Platform Partner');

        $isPaused = $industry->status === 'paused';

        return [
            'sl_no' => $slNo,
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'industry' => $industry->industry,
            'industry_type' => $industry->industry,
            'logo_url' => $this->formatLogoUrl($industry->logo),
            'website' => $industry->website,
            'tagline' => $industry->tagline,
            'description' => $industry->description,
            'company_size' => $industry->company_size,
            'views_count' => (int) ($industry->views_count ?? 0),
            'status' => $industry->status ?? 'active',
            'is_paused' => $isPaused,
            'paused_at' => $industry->paused_at?->toIso8601String(),
            'created_at' => $industry->created_at?->toIso8601String(),
            'created_at_formatted' => $industry->created_at?->format('d M Y'),
            'owner' => $creator ? [
                'id' => $creator->id,
                'full_name' => $creatorName,
                'first_name' => $creator->first_name,
                'last_name' => $creator->last_name,
                'username' => $creator->username,
                'email' => $creator->email,
                'mobile' => $creator->mobile,
                'profile_image_url' => $creator->profile_image_url,
                'title' => $creator->title,
                'location' => $location,
                'is_verified' => (bool) $creator->is_verified,
                'is_suspended' => (bool) $creator->is_suspended,
            ] : null,
        ];
    }

    /**
     * Find industry by ID or slug.
     */
    protected function findIndustry(int|string $id, array $relations = []): Industry
    {
        $query = Industry::withTrashed();

        if (! empty($relations)) {
            $query->with($relations);
        }

        if (is_numeric($id)) {
            return $query->where('id', (int) $id)->firstOrFail();
        }

        return $query->where('slug', (string) $id)->orWhere('id', (int) $id)->firstOrFail();
    }

    /**
     * Helper to safely format logo URLs.
     */
    protected function formatLogoUrl(?string $logo): ?string
    {
        if (empty($logo)) {
            return null;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset('storage/'.ltrim($logo, '/'));
    }
}
