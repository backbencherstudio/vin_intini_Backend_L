<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class SubscriptionManagementController extends Controller
{
    public function __construct(private StripeService $stripe) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Subscription::with(['user', 'plan'])->latest();

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

        $subscriptions = $query->paginate($validated['per_page'] ?? 15)->withQueryString();

        $data = $subscriptions->map(fn (Subscription $subscription) => [
            'id' => $subscription->id,
            'subscriber' => [
                'name' => trim(($subscription->user->first_name ?? '').' '.($subscription->user->last_name ?? '')),
                'image' => $subscription->user->profile_image_url,
            ],
            'plan' => $subscription->plan ? [
                'name' => $subscription->plan->name,
                'amount' => $subscription->plan->billing_rate,
                'billing_cycle' => $subscription->plan->billing_cycle,
            ] : null,
            'status' => $subscription->status,
            'billing_cycle' => $subscription->plan?->billing_cycle,
            'next_billing_date' => $subscription->current_period_end?->toIso8601String(),
            'days_left' => $this->daysLeft($subscription),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'joined_at' => $subscription->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ], 200);
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        if ($subscription->status === 'canceled') {
            return response()->json([
                'success' => false,
                'message' => 'This subscription is already canceled.',
            ], 422);
        }

        if ($subscription->platform !== 'stripe' || ! $subscription->provider_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription cannot be canceled from here.',
            ], 422);
        }

        try {
            $this->stripe->cancelSubscription($subscription->provider_subscription_id, true);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: '.$e->getMessage(),
            ], 422);
        }

        $subscription->update([
            'cancel_at_period_end' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription will be canceled at the end of the current billing period.',
            'data' => $subscription,
        ], 200);
    }

    private function daysLeft(Subscription $subscription): ?int
    {
        if (! $subscription->current_period_end) {
            return null;
        }

        $secondsLeft = $subscription->current_period_end->timestamp - now()->timestamp;

        return max(0, (int) ceil($secondsLeft / 86400));
    }
}
