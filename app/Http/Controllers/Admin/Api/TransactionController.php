<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,succeeded,failed,refunded'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Transaction::with(['user', 'plan'])->latest();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['plan_id'])) {
            $query->where('plan_id', $validated['plan_id']);
        }

        if (! empty($validated['search'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->whereRaw(
                    "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                    ['%'.$validated['search'].'%'],
                )
                    ->orWhere('email', 'like', '%'.$validated['search'].'%');
            });
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $transactions = $query->paginate($validated['per_page'] ?? 15)->withQueryString();

        $data = $transactions->map(fn (Transaction $transaction) => [
            'id' => $transaction->id,
            'transaction_id' => $transaction->provider_transaction_id,
            'subscriber' => [
                'name' => trim(($transaction->user->first_name ?? '').' '.($transaction->user->last_name ?? '')),
                'image' => $transaction->user->profile_image_url,
            ],
            'plan' => $transaction->plan ? [
                'name' => $transaction->plan->name,
                'amount' => $transaction->plan->billing_rate,
                'billing_cycle' => $transaction->plan->billing_cycle,
            ] : null,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'card_brand' => $transaction->card_brand,
            'card_last4' => $transaction->card_last4,
            'refunded_amount' => $transaction->refunded_amount,
            'purchased_at' => ($transaction->paid_at ?? $transaction->created_at)?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    public function overview(): JsonResponse
    {
        $now = now();

        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->endOfMonth();
        $previousStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $cards = [
            'subscribers' => [
                'label' => 'Subscribers',
                'current' => Subscription::whereBetween('created_at', [$currentStart, $currentEnd])->count(),
                'previous' => Subscription::whereBetween('created_at', [$previousStart, $previousEnd])->count(),
            ],
            'total_revenue' => [
                'label' => 'Total Revenue',
                'current' => (float) Transaction::where('status', 'succeeded')
                    ->whereBetween('paid_at', [$currentStart, $currentEnd])
                    ->sum('amount'),
                'previous' => (float) Transaction::where('status', 'succeeded')
                    ->whereBetween('paid_at', [$previousStart, $previousEnd])
                    ->sum('amount'),
            ],
            'successful_payments' => [
                'label' => 'Successful Payments',
                'current' => Transaction::where('status', 'succeeded')
                    ->whereBetween('paid_at', [$currentStart, $currentEnd])
                    ->count(),
                'previous' => Transaction::where('status', 'succeeded')
                    ->whereBetween('paid_at', [$previousStart, $previousEnd])
                    ->count(),
            ],
            'refunded_amount' => [
                'label' => 'Refunded Amount',
                'current' => (float) Transaction::where('refunded_amount', '>', 0)
                    ->whereBetween('refunded_at', [$currentStart, $currentEnd])
                    ->sum('refunded_amount'),
                'previous' => (float) Transaction::where('refunded_amount', '>', 0)
                    ->whereBetween('refunded_at', [$previousStart, $previousEnd])
                    ->sum('refunded_amount'),
            ],
            'failed_payments' => [
                'label' => 'Failed Payments',
                'current' => Transaction::where('status', 'failed')
                    ->whereBetween('created_at', [$currentStart, $currentEnd])
                    ->count(),
                'previous' => Transaction::where('status', 'failed')
                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                    ->count(),
            ],
        ];

        $data = collect($cards)->map(function (array $card) {
            $current = $card['current'];
            $previous = $card['previous'];
            $difference = $current - $previous;

            return [
                'label' => $card['label'],
                'value' => $current,
                'previous_value' => $previous,
                'difference' => $difference,
                'change_percent' => $previous > 0 ? round(($difference / $previous) * 100, 2) : null,
                'direction' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat'),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data], 200);
    }
}
