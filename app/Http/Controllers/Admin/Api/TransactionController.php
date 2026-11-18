<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateFilterParams($request, true);
        $baseQuery = $this->buildFilteredBaseQuery($validated);

        // Compute tab counts respecting current search/status/date filters
        $allCount = (clone $baseQuery)->count();
        $subscriptionCount = (clone $baseQuery)->whereNull('ad_campaign_id')->count();
        $boostingCount = (clone $baseQuery)->whereNotNull('ad_campaign_id')->count();

        $query = (clone $baseQuery)
            ->with($this->transactionRelations())
            ->latest('id');

        $this->applyTypeAndPackageFilters($query, $validated);

        $transactions = $query->paginate($validated['per_page'] ?? 15)->withQueryString();

        $data = $transactions->getCollection()
            ->map(fn (Transaction $transaction): array => $this->formatTransaction($transaction, false))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'counts' => [
                'all' => $allCount,
                'subscription' => $subscriptionCount,
                'boosting' => $boostingCount,
            ],
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $this->validateFilterParams($request, false);
        $query = $this->buildFilteredBaseQuery($validated)
            ->with($this->transactionRelations())
            ->latest('id');

        $this->applyTypeAndPackageFilters($query, $validated);

        $typeSlug = $validated['type'] ?? 'all';
        $fileName = sprintf('mindunite-transactions-%s-%s.csv', $typeSlug, now()->format('Y-m-d'));

        $sanitizeCsv = static function (mixed $value): string {
            $str = (string) ($value ?? '');
            if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str) === 1 && ! is_numeric($str)) {
                return "'".$str;
            }

            return $str;
        };

        return response()->streamDownload(function () use ($query, $sanitizeCsv): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for proper Excel encoding
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Invoice Number',
                'Transaction ID',
                'Type',
                'Customer Name',
                'Customer Email',
                'Username',
                'Industry / Company',
                'Plan / Package',
                'Billing Cycle',
                'Boosted Item',
                'Boost Duration (Days)',
                'Network',
                'Campaign Status',
                'Amount',
                'Currency',
                'Refunded Amount',
                'Net Amount',
                'Payment Status',
                'Card Brand',
                'Card Last 4',
                'Paid At (UTC)',
            ]);

            $query->chunk(200, function ($chunk) use ($handle, $sanitizeCsv): void {
                foreach ($chunk as $transaction) {
                    $row = $this->formatTransaction($transaction, false);
                    $isBoosting = $row['type'] === 'boosting';
                    $boosting = $row['boosting'];
                    $plan = $row['plan'];

                    $networkType = is_array($boosting) && ! empty($boosting['network_type'])
                        ? ucfirst((string) $boosting['network_type'])
                        : '';
                    $campaignStatus = is_array($boosting) && ! empty($boosting['campaign_status'])
                        ? ucfirst((string) $boosting['campaign_status'])
                        : '';

                    fputcsv($handle, [
                        $sanitizeCsv($row['invoice_number']),
                        $sanitizeCsv($row['transaction_id'] ?? ''),
                        $isBoosting ? 'Boosting' : 'Subscription',
                        $sanitizeCsv($row['subscriber']['name'] ?? ''),
                        $sanitizeCsv($row['subscriber']['email'] ?? ''),
                        $sanitizeCsv($row['subscriber']['username'] ?? ''),
                        $sanitizeCsv($boosting['industry']['name'] ?? ''),
                        $sanitizeCsv($isBoosting ? ($boosting['package_name'] ?? 'Custom Boost') : ($plan['name'] ?? '')),
                        $sanitizeCsv($isBoosting ? 'One-time' : ucfirst((string) ($plan['billing_cycle'] ?? ''))),
                        $sanitizeCsv($boosting['item']['name'] ?? ''),
                        $isBoosting ? ($boosting['duration_days'] ?? '') : '',
                        $sanitizeCsv($networkType),
                        $sanitizeCsv($campaignStatus),
                        $row['amount'],
                        $row['currency'],
                        $row['refunded_amount'],
                        $row['net_amount'],
                        ucfirst((string) $row['status']),
                        strtoupper((string) ($row['card_brand'] ?? '')),
                        $row['card_last4'] ?? '',
                        $row['purchased_at'] ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilterParams(Request $request, bool $includePagination): array
    {
        $rules = [
            'type' => ['sometimes', 'nullable', 'string', 'in:all,subscription,boosting'],
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,succeeded,failed,refunded,partially_refunded'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
            'boost_package_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
        ];

        if ($includePagination) {
            $rules['per_page'] = ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildFilteredBaseQuery(array $validated): Builder
    {
        $baseQuery = Transaction::query();

        if (! empty($validated['status'])) {
            if ($validated['status'] === 'refunded') {
                $baseQuery->whereIn('status', ['refunded', 'partially_refunded']);
            } else {
                $baseQuery->where('status', $validated['status']);
            }
        }

        if (! empty($validated['search'])) {
            $term = trim($validated['search']);
            $baseQuery->where(function (Builder $q) use ($term): void {
                $q->where('provider_transaction_id', 'like', '%'.$term.'%')
                    ->orWhereHas('user', function (Builder $uq) use ($term): void {
                        $uq->withTrashed()
                            ->where(function (Builder $inner) use ($term): void {
                                $inner->whereRaw(
                                    "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                    ['%'.$term.'%'],
                                )
                                    ->orWhere('email', 'like', '%'.$term.'%')
                                    ->orWhere('username', 'like', '%'.$term.'%');
                            });
                    })
                    ->orWhereHas('plan', function (Builder $pq) use ($term): void {
                        $pq->where('name', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('adCampaign', function (Builder $aq) use ($term): void {
                        $aq->whereHas('boostPackage', fn (Builder $bp) => $bp->where('name', 'like', '%'.$term.'%'))
                            ->orWhereHas('industry', fn (Builder $iq) => $iq->withTrashed()->where('name', 'like', '%'.$term.'%'))
                            ->orWhereHasMorph(
                                'boostable',
                                [IndustryProduct::class],
                                fn (Builder $bq) => $bq->withTrashed()->where('product_name', 'like', '%'.$term.'%'),
                            );
                    });
            });
        }

        if (! empty($validated['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $validated['date_to']);
        }

        return $baseQuery;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyTypeAndPackageFilters(Builder $query, array $validated): void
    {
        $type = $validated['type'] ?? 'all';
        if ($type === 'subscription') {
            $query->whereNull('ad_campaign_id');
        } elseif ($type === 'boosting') {
            $query->whereNotNull('ad_campaign_id');
        }

        if (! empty($validated['plan_id'])) {
            $query->where('plan_id', $validated['plan_id']);
        }

        if (! empty($validated['boost_package_id'])) {
            $pkg = $validated['boost_package_id'];
            if ($pkg === 'custom') {
                $query->whereHas('adCampaign', fn (Builder $aq) => $aq->whereNull('boost_package_id'));
            } elseif (is_numeric($pkg)) {
                $query->whereHas('adCampaign', fn (Builder $aq) => $aq->where('boost_package_id', (int) $pkg));
            }
        }
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with($this->transactionRelations())->find($id);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTransaction($transaction, true),
        ], 200);
    }

    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'nullable', 'string', 'in:all,subscription,boosting'],
            'period' => ['sometimes', 'nullable', 'string', 'in:all,this_month'],
        ]);

        $type = $validated['type'] ?? 'all';
        $period = $validated['period'] ?? 'all';

        $now = now();
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->endOfMonth();
        $previousStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $scopedQuery = function () use ($type): Builder {
            $q = Transaction::query();
            if ($type === 'subscription') {
                $q->whereNull('ad_campaign_id');
            } elseif ($type === 'boosting') {
                $q->whereNotNull('ad_campaign_id');
            }

            return $q;
        };

        // Revenue breakdowns (all-time & monthly)
        $subRevenueAll = (float) Transaction::whereNull('ad_campaign_id')->where('status', 'succeeded')->sum('amount');
        $subRevenueCurrent = (float) Transaction::whereNull('ad_campaign_id')->where('status', 'succeeded')
            ->whereBetween('paid_at', [$currentStart, $currentEnd])->sum('amount');
        $subRevenuePrev = (float) Transaction::whereNull('ad_campaign_id')->where('status', 'succeeded')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])->sum('amount');

        $boostRevenueAll = (float) Transaction::whereNotNull('ad_campaign_id')->where('status', 'succeeded')->sum('amount');
        $boostRevenueCurrent = (float) Transaction::whereNotNull('ad_campaign_id')->where('status', 'succeeded')
            ->whereBetween('paid_at', [$currentStart, $currentEnd])->sum('amount');
        $boostRevenuePrev = (float) Transaction::whereNotNull('ad_campaign_id')->where('status', 'succeeded')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])->sum('amount');

        $totalRevenueAll = (float) $scopedQuery()->where('status', 'succeeded')->sum('amount');
        $totalRevenueCurrent = (float) $scopedQuery()->where('status', 'succeeded')
            ->whereBetween('paid_at', [$currentStart, $currentEnd])->sum('amount');
        $totalRevenuePrev = (float) $scopedQuery()->where('status', 'succeeded')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])->sum('amount');

        $succeededAll = $scopedQuery()->where('status', 'succeeded')->count();
        $succeededCurrent = $scopedQuery()->where('status', 'succeeded')
            ->whereBetween('paid_at', [$currentStart, $currentEnd])->count();
        $succeededPrev = $scopedQuery()->where('status', 'succeeded')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])->count();

        $totalTxnAll = $scopedQuery()->count();
        $successRate = $totalTxnAll > 0 ? round(($succeededAll / $totalTxnAll) * 100, 1) : 0;

        $refundedAll = (float) $scopedQuery()->where('refunded_amount', '>', 0)->sum('refunded_amount');
        $refundedCurrent = (float) $scopedQuery()->where('refunded_amount', '>', 0)
            ->whereBetween('refunded_at', [$currentStart, $currentEnd])->sum('refunded_amount');
        $refundedPrev = (float) $scopedQuery()->where('refunded_amount', '>', 0)
            ->whereBetween('refunded_at', [$previousStart, $previousEnd])->sum('refunded_amount');

        $failedAll = $scopedQuery()->where('status', 'failed')->count();
        $failedCurrent = $scopedQuery()->where('status', 'failed')
            ->whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $failedPrev = $scopedQuery()->where('status', 'failed')
            ->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $subTxnCount = Transaction::whereNull('ad_campaign_id')->where('status', 'succeeded')->count();
        $boostTxnCount = Transaction::whereNotNull('ad_campaign_id')->where('status', 'succeeded')->count();
        $activeSubscribers = Subscription::whereIn('status', ['active', 'trialing'])->count();
        $activeCampaigns = AdCampaign::where('status', 'active')->count();

        $makeCard = function (
            string $key,
            string $label,
            string $format,
            float|int $allTimeValue,
            float|int $monthlyCurrent,
            float|int $monthlyPrevious,
            string $subtitle,
        ) use ($period): array {
            $difference = $monthlyCurrent - $monthlyPrevious;

            return [
                'key' => $key,
                'label' => $label,
                'format' => $format,
                'value' => $period === 'this_month' ? $monthlyCurrent : $allTimeValue,
                'all_time_value' => $allTimeValue,
                'monthly_value' => $monthlyCurrent,
                'previous_value' => $monthlyPrevious,
                'difference' => $difference,
                'change_percent' => $monthlyPrevious > 0 ? round(($difference / $monthlyPrevious) * 100, 2) : null,
                'direction' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat'),
                'subtitle' => $subtitle,
            ];
        };

        $overallRevenueAll = $subRevenueAll + $boostRevenueAll;
        $overallRevenueCurrent = $subRevenueCurrent + $boostRevenueCurrent;
        $overallRevenuePrev = $subRevenuePrev + $boostRevenuePrev;

        $cards = [
            $makeCard(
                'total_revenue',
                'Total Revenue',
                'currency',
                $overallRevenueAll,
                $overallRevenueCurrent,
                $overallRevenuePrev,
                sprintf('Sub: $%s • Boost: $%s', number_format($subRevenueAll, 2), number_format($boostRevenueAll, 2)),
            ),
            $makeCard(
                'subscription_revenue',
                'Subscription Revenue',
                'currency',
                $subRevenueAll,
                $subRevenueCurrent,
                $subRevenuePrev,
                sprintf('%d payments • %d active subs', $subTxnCount, $activeSubscribers),
            ),
            $makeCard(
                'boosting_revenue',
                'Boosting Revenue',
                'currency',
                $boostRevenueAll,
                $boostRevenueCurrent,
                $boostRevenuePrev,
                sprintf('%d payments • %d active boosts', $boostTxnCount, $activeCampaigns),
            ),
            $makeCard(
                'successful_payments',
                'Successful Payments',
                'number',
                $succeededAll,
                $succeededCurrent,
                $succeededPrev,
                sprintf('%.1f%% success rate', $successRate),
            ),
            $makeCard(
                'refunded_amount',
                'Refunded Amount',
                'currency',
                $refundedAll,
                $refundedCurrent,
                $refundedPrev,
                sprintf('%d failed payments', $failedAll),
            ),
        ];

        return response()->json([
            'success' => true,
            'period' => $period,
            'type' => $type,
            'data' => $cards,
            'summary' => [
                'total_revenue' => $totalRevenueAll,
                'subscription_revenue' => $subRevenueAll,
                'boosting_revenue' => $boostRevenueAll,
                'successful_payments' => $succeededAll,
                'failed_payments' => $failedAll,
                'refunded_amount' => $refundedAll,
                'active_subscribers' => $activeSubscribers,
                'active_campaigns' => $activeCampaigns,
            ],
        ], 200);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function transactionRelations(): array
    {
        return [
            'user' => fn ($q) => $q->withTrashed(),
            'plan',
            'subscription',
            'adCampaign.boostPackage',
            'adCampaign.industry' => fn ($q) => $q->withTrashed(),
            'adCampaign.boostable' => function (MorphTo $morphTo): void {
                $morphTo->constrain([
                    IndustryProduct::class => fn (Builder $q) => $q->withTrashed(),
                ]);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTransaction(Transaction $transaction, bool $detailed = false): array
    {
        $isBoosting = $transaction->ad_campaign_id !== null;
        $type = $isBoosting ? 'boosting' : 'subscription';
        $user = $transaction->user;
        $campaign = $transaction->adCampaign;
        $plan = $transaction->plan;
        $subscription = $transaction->subscription;

        $purchasedAt = $transaction->paid_at ?? $transaction->created_at;
        $invoiceYear = $purchasedAt ? $purchasedAt->format('Y') : now()->format('Y');
        $invoiceNumber = sprintf('INV-%s-%06d', $invoiceYear, $transaction->id);

        $amount = (float) $transaction->amount;
        $refundedAmount = (float) ($transaction->refunded_amount ?? 0);
        $netAmount = max(0, round($amount - $refundedAmount, 2));

        $boostingData = null;
        if ($isBoosting && $campaign) {
            $boostable = $campaign->boostable;
            $itemData = null;

            if ($boostable instanceof IndustryProduct) {
                $itemData = [
                    'id' => $boostable->id,
                    'code' => $boostable->product_id,
                    'name' => $boostable->product_name,
                    'slug' => $boostable->slug,
                    'image' => $boostable->image_url,
                    'type' => 'product',
                ];
            } elseif ($boostable) {
                $itemData = [
                    'id' => $boostable->id,
                    'code' => null,
                    'name' => $boostable->title ?? $boostable->name ?? ('Item #'.$boostable->id),
                    'slug' => $boostable->slug ?? null,
                    'image' => null,
                    'type' => $campaign->ad_type ?? 'item',
                ];
            }

            $industry = $campaign->industry;
            $industryData = $industry ? [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'logo' => $industry->logo,
                'address' => $industry->address,
                'website' => $industry->website,
            ] : null;

            $packageName = $campaign->boostPackage?->name ?? 'Custom Boost';

            $boostingData = [
                'campaign_id' => $campaign->id,
                'package_id' => $campaign->boost_package_id,
                'package_name' => $packageName,
                'is_custom' => $campaign->boost_package_id === null,
                'duration_days' => (int) ($campaign->duration_days ?? 0),
                'ad_type' => $campaign->ad_type ?? 'product',
                'network_type' => $campaign->network_type,
                'placements' => $campaign->placements ?? [],
                'campaign_status' => $campaign->status instanceof \BackedEnum
                    ? $campaign->status->value
                    : (string) ($campaign->status ?? 'pending'),
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
                'total_impressions' => (int) ($campaign->total_impressions ?? 0),
                'total_clicks' => (int) ($campaign->total_clicks ?? 0),
                'payment_platform' => $campaign->payment_platform ?? 'stripe',
                'item' => $itemData,
                'industry' => $industryData,
            ];
        }

        $subscriptionData = $subscription ? [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'platform' => $subscription->platform ?? 'stripe',
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'current_period_start' => $subscription->current_period_start?->toIso8601String(),
            'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            'cancel_at_period_end' => (bool) $subscription->cancel_at_period_end,
        ] : null;

        $payload = [
            'id' => $transaction->id,
            'invoice_number' => $invoiceNumber,
            'transaction_id' => $transaction->provider_transaction_id,
            'type' => $type,
            'subscriber' => [
                'id' => $user?->id,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Unknown User',
                'username' => $user?->username,
                'email' => $user?->email,
                'image' => $user?->profile_image_url,
            ],
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'amount' => $plan->billing_rate,
                'billing_cycle' => $plan->billing_cycle,
            ] : null,
            'subscription' => $subscriptionData,
            'boosting' => $boostingData,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => strtoupper($transaction->currency ?: 'USD'),
            'status' => $transaction->status,
            'card_brand' => $transaction->card_brand,
            'card_last4' => $transaction->card_last4,
            'refunded_amount' => number_format($refundedAmount, 2, '.', ''),
            'net_amount' => number_format($netAmount, 2, '.', ''),
            'refunded_at' => $transaction->refunded_at?->toIso8601String(),
            'purchased_at' => $purchasedAt?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['checkout_session_id'] = $transaction->checkout_session_id;
            $payload['created_at'] = $transaction->created_at?->toIso8601String();
            $payload['issued_by'] = [
                'name' => 'MindUnite',
                'tagline' => 'Psychology & Neuroscience Professional Network',
                'email' => 'billing@mindunite.com',
            ];
            $payload['line_items'] = $this->buildInvoiceLineItems($transaction, $type, $plan, $boostingData, $amount);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>|null  $boostingData
     * @return array<int, array<string, mixed>>
     */
    private function buildInvoiceLineItems(
        Transaction $transaction,
        string $type,
        mixed $plan,
        ?array $boostingData,
        float $amount,
    ): array {
        if ($type === 'boosting' && $boostingData) {
            $pkg = $boostingData['package_name'];
            $days = $boostingData['duration_days'];
            $itemName = $boostingData['item']['name'] ?? 'Product';
            $network = $boostingData['network_type'] ? ucfirst($boostingData['network_type']).' Network' : null;
            $subParts = array_filter([
                $itemName ? "Boosted Item: {$itemName}" : null,
                $days > 0 ? "Duration: {$days} days" : null,
                $network,
            ]);

            return [[
                'title' => "{$pkg} — Ad Campaign #{$boostingData['campaign_id']}",
                'description' => implode(' • ', $subParts),
                'quantity' => 1,
                'unit_price' => number_format($amount, 2, '.', ''),
                'total' => number_format($amount, 2, '.', ''),
            ]];
        }

        $planName = $plan?->name ?? 'Subscription Plan';
        $cycle = $plan?->billing_cycle ? ucfirst($plan->billing_cycle).' Billing' : 'Recurring Plan';

        return [[
            'title' => "{$planName} Subscription",
            'description' => $cycle,
            'quantity' => 1,
            'unit_price' => number_format($amount, 2, '.', ''),
            'total' => number_format($amount, 2, '.', ''),
        ]];
    }
}
