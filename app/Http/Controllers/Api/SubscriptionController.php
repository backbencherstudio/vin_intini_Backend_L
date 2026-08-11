<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionOtpMail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Subscription as StripeSubscription;

class SubscriptionController extends Controller
{
    public function __construct(private StripeService $stripe) {}

    public function plans(): JsonResponse
    {
        $plans = Plan::where('status', 'active')
            ->whereNotNull('stripe_price_id')
            ->orderBy('billing_rate')
            ->get(['id', 'name', 'short_description', 'billing_cycle', 'billing_rate', 'badge_color', 'features', 'stripe_price_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'stripe_public_key' => config('services.stripe.key'),
                'plans' => $plans,
            ],
        ], 200);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $user = $request->user();

        if ($this->hasActiveSubscription($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription.',
            ], 422);
        }

        return $this->sendOtpToUser($user);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'otp' => ['required', 'digits:4'],
            'payment_method' => ['required', 'string'],
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

        if ($this->hasActiveSubscription($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription.',
            ], 422);
        }

        if (! $this->verifyOtp($user, $validated['otp'])) {
            return $this->invalidOtpResponse($user);
        }

        return $this->completeSubscription($user, $plan, $validated['payment_method']);
    }

    public function status(Request $request): JsonResponse
    {
        $subscription = Subscription::with('plan')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if ($subscription) {
            $this->stripe->hydrateSubscriptionDates($subscription);
        }

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

    private function sendOtpToUser(User $user): JsonResponse
    {
        if ($user->otp_expires_at && $user->otp_expires_at->greaterThan(now())) {
            $remainingSeconds = (int) ceil(now()->diffInSeconds($user->otp_expires_at, false));

            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
            ], 429);
        }

        $otp = random_int(1000, 9999);

        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(3),
        ]);

        Mail::to($user->email)->queue(new SubscriptionOtpMail($otp));

        $data = [
            'email' => $user->email,
            'requires_verification' => true,
        ];

        if (app()->environment('local')) {
            $data['debug_otp'] = (string) $otp;
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email. Submit the request again with the OTP and payment method to complete the subscription.',
            'data' => $data,
        ], 200);
    }

    private function hasActiveSubscription(User $user): bool
    {
        return Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'paused'])
            ->exists();
    }

    private function verifyOtp(User $user, string $otp): bool
    {
        if (! $user->otp || (string) $user->otp !== $otp) {
            return false;
        }

        if (! $user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
            return false;
        }

        return true;
    }

    private function invalidOtpResponse(User $user): JsonResponse
    {
        $expired = $user->otp_expires_at && now()->greaterThan($user->otp_expires_at);

        return response()->json([
            'success' => false,
            'message' => $expired ? 'OTP expired. Please request a new one.' : 'Invalid OTP.',
            'data' => ['can_resend_otp' => $expired],
        ], 400);
    }

    private function completeSubscription(User $user, Plan $plan, string $paymentMethodId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $customer = $this->stripe->getOrCreateCustomer($user);
            $this->stripe->attachPaymentMethod($paymentMethodId, $customer->id);

            $stripeSubscription = $this->stripe->createSubscription(
                $plan,
                $customer->id,
                $user->id,
                $paymentMethodId,
            );

            $paymentIntent = data_get($stripeSubscription, 'latest_invoice.payment_intent');

            if ($stripeSubscription->status === 'incomplete' && $paymentIntent?->status === 'requires_action') {
                $this->storeSubscriptionRecord($stripeSubscription, $plan, $user, $customer->id);

                DB::commit();
                $user->update(['otp' => null, 'otp_expires_at' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Additional authentication is required to complete your payment.',
                    'data' => [
                        'payment_intent_client_secret' => $paymentIntent->client_secret,
                        'payment_status' => $paymentIntent->status,
                    ],
                ], 200);
            }

            if (! in_array($stripeSubscription->status, ['active', 'trialing'])) {
                throw new InvalidRequestException("Subscription could not be activated (status: {$stripeSubscription->status}).");
            }

            $subscription = $this->storeSubscriptionRecord($stripeSubscription, $plan, $user, $customer->id);

            DB::commit();
            $user->update(['otp' => null, 'otp_expires_at' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'provider_subscription_id' => $subscription->provider_subscription_id,
                        'status' => $subscription->status,
                        'plan' => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'billing_cycle' => $plan->billing_cycle,
                            'billing_rate' => $plan->billing_rate,
                        ],
                        'expires_at' => $subscription->current_period_end?->toIso8601String(),
                    ],
                ],
            ], 201);
        } catch (ApiErrorException $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Subscription payment failed. Please try again.',
                'errors' => ['error' => $exception->getMessage()],
            ], 422);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Subscription failed. Please try again.',
            ], 500);
        }
    }

    private function storeSubscriptionRecord(
        StripeSubscription $stripeSubscription,
        Plan $plan,
        User $user,
        string $customerId,
    ): Subscription {
        [$periodStart, $periodEnd] = $this->stripe->periodDatesFromSubscription($stripeSubscription);

        $priceId = data_get($stripeSubscription, 'items.data.0.price.id') ?? $plan->stripe_price_id;
        $productId = data_get($stripeSubscription, 'items.data.0.price.product') ?? $plan->stripe_product_id;

        $subscription = Subscription::updateOrCreate(
            [
                'provider_subscription_id' => $stripeSubscription->id,
                'platform' => 'stripe',
            ],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'provider_customer_id' => $customerId,
                'product_id' => $productId,
                'price_id' => $priceId,
                'status' => $stripeSubscription->status ?? 'active',
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
                'canceled_at' => null,
                'ends_at' => null,
            ],
        );

        $this->storeTransactionRecord($stripeSubscription, $subscription, $user, $plan);

        return $subscription;
    }

    private function storeTransactionRecord(
        StripeSubscription $stripeSubscription,
        Subscription $subscription,
        User $user,
        Plan $plan,
    ): void {
        $paymentIntent = data_get($stripeSubscription, 'latest_invoice.payment_intent');

        if (! $paymentIntent?->id) {
            return;
        }

        Transaction::updateOrCreate(
            ['provider_transaction_id' => $paymentIntent->id],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'amount' => ($paymentIntent->amount ?? 0) / 100,
                'currency' => $paymentIntent->currency ?? 'usd',
                'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
                'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
                'status' => $paymentIntent->status === 'succeeded' ? 'succeeded' : 'pending',
                'refunded_amount' => 0,
                'paid_at' => $paymentIntent->status === 'succeeded' ? now() : null,
            ],
        );
    }
}
