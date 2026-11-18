<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\AdCampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;
use App\Models\Transaction;
use App\Services\Advertising\ProductBoostProjection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdCampaignAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:all,active,paused,completed,cancelled,pending'],
            'network_type' => ['sometimes', 'nullable', 'string', 'in:psychology,neuroscience'],
            'boost_package_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $baseQuery = AdCampaign::query();

        if (! empty($validated['network_type'])) {
            $baseQuery->where('network_type', $validated['network_type']);
        }

        if (! empty($validated['boost_package_id'])) {
            $pkg = $validated['boost_package_id'];
            if ($pkg === 'custom') {
                $baseQuery->whereNull('boost_package_id');
            } elseif (is_numeric($pkg)) {
                $baseQuery->where('boost_package_id', (int) $pkg);
            }
        }

        if (! empty($validated['search'])) {
            $term = trim($validated['search']);
            $baseQuery->where(function (Builder $q) use ($term): void {
                if (is_numeric($term)) {
                    $q->orWhere('id', (int) $term);
                }

                $q->orWhere('stripe_payment_intent_id', 'like', '%'.$term.'%')
                    ->orWhereHas('boostPackage', fn (Builder $bp) => $bp->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('industry', fn (Builder $iq) => $iq->withTrashed()->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('user', function (Builder $uq) use ($term): void {
                        $uq->withTrashed()->where(function (Builder $inner) use ($term): void {
                            $inner->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                ['%'.$term.'%'],
                            )
                                ->orWhere('email', 'like', '%'.$term.'%')
                                ->orWhere('username', 'like', '%'.$term.'%');
                        });
                    })
                    ->orWhereHasMorph(
                        'boostable',
                        [IndustryProduct::class],
                        fn (Builder $bq) => $bq->withTrashed()->where(function (Builder $inner) use ($term): void {
                            $inner->where('product_name', 'like', '%'.$term.'%')
                                ->orWhere('product_id', 'like', '%'.$term.'%');
                        }),
                    );
            });
        }

        if (! empty($validated['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Tab counts respecting current non-status filters
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', AdCampaignStatus::ACTIVE->value)->count(),
            'paused' => (clone $baseQuery)->where('status', AdCampaignStatus::PAUSED->value)->count(),
            'completed' => (clone $baseQuery)->where('status', AdCampaignStatus::COMPLETED->value)->count(),
            'cancelled' => (clone $baseQuery)->where('status', AdCampaignStatus::CANCELLED->value)->count(),
            'pending' => (clone $baseQuery)->where('status', AdCampaignStatus::PENDING->value)->count(),
        ];

        $query = (clone $baseQuery)
            ->with($this->campaignRelations())
            ->latest('id');

        $status = $validated['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $campaigns = $query->paginate($validated['per_page'] ?? 10)->withQueryString();

        $campaignIds = $campaigns->getCollection()->pluck('id')->all();
        $transactionsByCampaign = Transaction::whereIn('ad_campaign_id', $campaignIds)
            ->latest('id')
            ->get()
            ->keyBy('ad_campaign_id');

        $data = $campaigns->getCollection()
            ->map(fn (AdCampaign $campaign): array => $this->formatCampaign(
                $campaign,
                $transactionsByCampaign->get($campaign->id),
            ))
            ->values();

        // Global overview metrics
        $totalImpressions = (int) AdCampaign::sum('total_impressions');
        $totalClicks = (int) AdCampaign::sum('total_clicks');
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;

        $presetRevenue = (float) AdCampaign::whereNotNull('boost_package_id')->sum('amount_paid');
        $customRevenue = (float) AdCampaign::whereNull('boost_package_id')->sum('amount_paid');

        $overview = [
            'total_campaigns' => AdCampaign::count(),
            'live_campaigns' => AdCampaign::where('status', AdCampaignStatus::ACTIVE->value)->count(),
            'paused_campaigns' => AdCampaign::where('status', AdCampaignStatus::PAUSED->value)->count(),
            'completed_campaigns' => AdCampaign::where('status', AdCampaignStatus::COMPLETED->value)->count(),
            'cancelled_campaigns' => AdCampaign::where('status', AdCampaignStatus::CANCELLED->value)->count(),
            'total_revenue' => round($presetRevenue + $customRevenue, 2),
            'preset_revenue' => round($presetRevenue, 2),
            'custom_revenue' => round($customRevenue, 2),
            'preset_count' => AdCampaign::whereNotNull('boost_package_id')->count(),
            'custom_count' => AdCampaign::whereNull('boost_package_id')->count(),
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'avg_ctr' => $avgCtr,
        ];

        return response()->json([
            'success' => true,
            'overview' => $overview,
            'counts' => $counts,
            'data' => $data,
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = AdCampaign::with($this->campaignRelations())->find($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        $transaction = Transaction::where('ad_campaign_id', $campaign->id)->latest('id')->first();

        return response()->json([
            'success' => true,
            'data' => $this->formatCampaign($campaign, $transaction),
        ], 200);
    }

    public function pause(int $id): JsonResponse
    {
        $campaign = AdCampaign::with($this->campaignRelations())->find($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        if ($campaign->status !== AdCampaignStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Only active campaigns can be paused.',
            ], 422);
        }

        $paused = DB::transaction(fn (): bool => $campaign->pause());

        if (! $paused) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to pause this campaign.',
            ], 422);
        }

        $campaign->refresh()->load($this->campaignRelations());
        $transaction = Transaction::where('ad_campaign_id', $campaign->id)->latest('id')->first();

        return response()->json([
            'success' => true,
            'message' => 'Campaign paused successfully. Remaining boost duration is frozen.',
            'data' => $this->formatCampaign($campaign, $transaction),
        ], 200);
    }

    public function resume(int $id): JsonResponse
    {
        $campaign = AdCampaign::with($this->campaignRelations())->find($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        if ($campaign->status !== AdCampaignStatus::PAUSED) {
            return response()->json([
                'success' => false,
                'message' => 'Only paused campaigns can be resumed.',
            ], 422);
        }

        $resumed = DB::transaction(fn (): bool => $campaign->resume());

        if (! $resumed) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resume this campaign.',
            ], 422);
        }

        $campaign->refresh()->load($this->campaignRelations());
        $transaction = Transaction::where('ad_campaign_id', $campaign->id)->latest('id')->first();

        return response()->json([
            'success' => true,
            'message' => 'Campaign resumed successfully. End date extended for paused duration.',
            'data' => $this->formatCampaign($campaign, $transaction),
        ], 200);
    }

    public function cancel(int $id): JsonResponse
    {
        $campaign = AdCampaign::with($this->campaignRelations())->find($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        if (! in_array($campaign->status, [
            AdCampaignStatus::ACTIVE,
            AdCampaignStatus::PAUSED,
            AdCampaignStatus::PENDING,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only active, paused, or pending campaigns can be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($campaign): void {
            $campaign->update([
                'status' => AdCampaignStatus::CANCELLED,
                'paused_at' => null,
                'paused_reminder_stage' => 0,
            ]);

            if ($campaign->boostable instanceof IndustryProduct) {
                ProductBoostProjection::projectFor($campaign->boostable);
            } elseif ($campaign->boostable && method_exists($campaign->boostable, 'update')) {
                $campaign->boostable->update(['is_boosted' => false]);
            }
        });

        $campaign->refresh()->load($this->campaignRelations());
        $transaction = Transaction::where('ad_campaign_id', $campaign->id)->latest('id')->first();

        return response()->json([
            'success' => true,
            'message' => 'Campaign cancelled successfully.',
            'data' => $this->formatCampaign($campaign, $transaction),
        ], 200);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function campaignRelations(): array
    {
        return [
            'user' => fn ($q) => $q->withTrashed(),
            'industry' => fn ($q) => $q->withTrashed(),
            'boostPackage',
            'boostable' => function (MorphTo $morphTo): void {
                $morphTo->constrain([
                    IndustryProduct::class => fn (Builder $q) => $q->withTrashed(),
                ]);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCampaign(AdCampaign $campaign, ?Transaction $transaction = null): array
    {
        $statusValue = $campaign->status instanceof \BackedEnum
            ? $campaign->status->value
            : (string) ($campaign->status ?? 'pending');

        $impressions = (int) ($campaign->total_impressions ?? 0);
        $clicks = (int) ($campaign->total_clicks ?? 0);
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        $boostable = $campaign->boostable;
        $item = null;
        if ($boostable instanceof IndustryProduct) {
            $item = [
                'id' => $boostable->id,
                'code' => $boostable->product_id,
                'name' => $boostable->product_name,
                'slug' => $boostable->slug,
                'image' => $boostable->image_url,
                'type' => 'product',
            ];
        } elseif ($boostable) {
            $item = [
                'id' => $boostable->id,
                'code' => null,
                'name' => $boostable->title ?? $boostable->name ?? ('Item #'.$boostable->id),
                'slug' => $boostable->slug ?? null,
                'image' => null,
                'type' => $campaign->ad_type ?? 'item',
            ];
        }

        $user = $campaign->user;
        $industry = $campaign->industry;

        $invoiceNumber = null;
        if ($transaction) {
            $purchasedAt = $transaction->paid_at ?? $transaction->created_at;
            $invoiceYear = $purchasedAt ? $purchasedAt->format('Y') : now()->format('Y');
            $invoiceNumber = sprintf('INV-%s-%06d', $invoiceYear, $transaction->id);
        }

        $remainingText = match ($statusValue) {
            AdCampaignStatus::CANCELLED->value => 'Cancelled',
            AdCampaignStatus::COMPLETED->value => 'Completed',
            AdCampaignStatus::PENDING->value => 'Pending',
            default => $campaign->getRemainingText(),
        };

        return [
            'id' => $campaign->id,
            'ad_type' => $campaign->ad_type ?? 'product',
            'network_type' => $campaign->network_type,
            'placements' => $campaign->placements ?? [],
            'package_id' => $campaign->boost_package_id,
            'package_name' => $campaign->boostPackage?->name ?? 'Custom Boost',
            'is_custom' => $campaign->boost_package_id === null,
            'duration_days' => (int) ($campaign->duration_days ?? 0),
            'amount_paid' => number_format((float) ($campaign->amount_paid ?? 0), 2, '.', ''),
            'currency' => strtoupper($campaign->currency ?: 'USD'),
            'status' => $statusValue,
            'starts_at' => $campaign->starts_at?->toIso8601String(),
            'ends_at' => $campaign->ends_at?->toIso8601String(),
            'paused_at' => $campaign->paused_at?->toIso8601String(),
            'remaining_days' => in_array($statusValue, [AdCampaignStatus::ACTIVE->value, AdCampaignStatus::PAUSED->value], true)
                ? $campaign->getRemainingDays()
                : 0,
            'remaining_text' => $remainingText,
            'total_impressions' => $impressions,
            'total_clicks' => $clicks,
            'ctr' => $ctr,
            'payment_platform' => $campaign->payment_platform ?? 'stripe',
            'payment_reference' => $campaign->stripe_payment_intent_id
                ?? $campaign->revenuecat_transaction_id
                ?? $transaction?->provider_transaction_id,
            'transaction_id' => $transaction?->id,
            'invoice_number' => $invoiceNumber,
            'created_at' => $campaign->created_at?->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Unknown User',
                'username' => $user->username,
                'email' => $user->email,
                'image' => $user->profile_image_url,
            ] : null,
            'industry' => $industry ? [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'logo' => $industry->logo,
                'address' => $industry->address,
                'website' => $industry->website,
            ] : null,
            'item' => $item,
        ];
    }
}
