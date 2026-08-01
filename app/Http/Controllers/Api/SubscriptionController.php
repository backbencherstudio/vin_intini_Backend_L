<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private StripeService $stripe) {}

    public function plans(): JsonResponse
    {
        $plans = Plan::where('status', 'active')
            ->orderBy('billing_rate')
            ->get();

        return response()->json(['success' => true, 'data' => $plans], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $user = $request->user();

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($plan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not available for subscription.',
            ], 422);
        }

        if (! $plan->stripe_price_id) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not connected to a payment provider yet.',
            ], 422);
        }

        $customer = $this->stripe->getOrCreateCustomer($user);
        $checkoutSession = $this->stripe->createCheckoutSession($plan, $user, $customer->id);

        return response()->json([
            'success' => true,
            'data' => [
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
            ],
        ], 200);
    }

    public function status(Request $request): JsonResponse
    {
        $subscription = Subscription::with('plan')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->isActive()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'isActive' => false,
                    'plan' => null,
                    'expiresAt' => null,
                    'willRenew' => false,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'isActive' => true,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'features' => $subscription->plan->features,
                    'billing_cycle' => $subscription->plan->billing_cycle,
                    'billing_rate' => $subscription->plan->billing_rate,
                ],
                'expiresAt' => $subscription->current_period_end?->toIso8601String(),
                'willRenew' => ! $subscription->cancel_at_period_end,
            ],
        ], 200);
    }
}
