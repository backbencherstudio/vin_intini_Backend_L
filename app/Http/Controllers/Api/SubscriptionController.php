<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpType;
use App\Enums\PlanFeature;
use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Mail\SubscriptionOtpMail;
use App\Models\Otp;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RevenueCatException;
use App\Services\RevenueCatService;
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
    public function __construct(
        private StripeService $stripe,
        private RevenueCatService $revenueCat,
    ) {}

    public function show(int $id): JsonResponse
    {
        $plan = Plan::where('status', 'active')->find($id);

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.',
            ], 404);
        }

        $plan->setAttribute('checkout_type', $plan->isRevenueCat() ? 'revenuecat' : 'stripe');
        $plan->setAttribute('features', $this->featureList($plan->features ?? []));

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
            ],
        ], 200);
    }

    public function plans(Request $request): JsonResponse
    {
        $plans = Plan::where('status', 'active')
            ->when($request->filled('billing_cycle'), fn ($query) => $query->where('billing_cycle', $request->string('billing_cycle')))
            ->where(function ($query) {
                $query->whereNotNull('stripe_price_id')
                    ->orWhereNotNull('revenuecat_store_identifier_ios')
                    ->orWhereNotNull('revenuecat_store_identifier_android');
            })
            ->orderBy('billing_rate')
            ->get(['id', 'name', 'short_description', 'billing_cycle', 'plan_type', 'billing_rate', 'badge_color', 'features', 'stripe_price_id', 'revenuecat_store_identifier_ios', 'revenuecat_store_identifier_android']);

        $plans->each(function (Plan $plan) {
            $plan->setAttribute('checkout_type', $plan->isRevenueCat() ? 'revenuecat' : 'stripe');
            $plan->setAttribute('features', $this->featureList($plan->features ?? []));
        });

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
            'checkout_type' => ['required', 'string', 'in:stripe,revenuecat'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($validated['checkout_type'] === 'revenuecat') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is purchased in the app store via RevenueCat. Complete the purchase in the app and your subscription will activate automatically.',
            ], 422);
        }

        if (! $plan->stripe_price_id) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not configured for Stripe payments.',
            ], 422);
        }

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
            'checkout_type' => ['required', 'string', 'in:stripe,revenuecat'],
        ]);

        $user = $request->user();

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($validated['checkout_type'] === 'revenuecat') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is purchased in the app store via RevenueCat. Complete the purchase in the app and your subscription will activate automatically.',
            ], 422);
        }

        if ($plan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not available for subscription.',
            ], 422);
        }

        if (! $plan->stripe_price_id) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not configured for Stripe payments.',
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

        $plan = $subscription->plan;

        return response()->json([
            'success' => true,
            'data' => [
                'isActive' => true,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'features' => $plan->features,
                    'billing_cycle' => $plan->billing_cycle,
                    'billing_rate' => $plan->billing_rate,
                ] : null,
                'expiresAt' => $subscription->current_period_end?->toIso8601String(),
                'willRenew' => ! $subscription->cancel_at_period_end,
            ],
        ], 200);
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription does not belong to you.',
            ], 403);
        }

        if ($subscription->status === 'canceled') {
            return response()->json([
                'success' => false,
                'message' => 'This subscription is already canceled.',
            ], 422);
        }

        if ($subscription->platform === 'stripe') {
            if (! $subscription->provider_subscription_id) {
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

            $subscription->update(['cancel_at_period_end' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription will be canceled at the end of the current billing period.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'cancel_at_period_end' => true,
                    ],
                ],
            ], 200);
        }

        if ($subscription->platform !== 'revenuecat') {
            return response()->json([
                'success' => false,
                'message' => 'This subscription platform is not supported for cancellation.',
            ], 422);
        }

        $entitlementId = $subscription->plan?->revenuecat_entitlement_identifier;

        if (! $entitlementId) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription is not linked to a RevenueCat entitlement.',
            ], 422);
        }

        $appUserId = $subscription->provider_customer_id ?? $this->revenueCat->appUserIdFor($user);

        try {
            $this->revenueCat->revokeEntitlements($appUserId, [$entitlementId]);
        } catch (RevenueCatException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: '.$e->getMessage(),
            ], 422);
        }

        $subscription->update([
            'status' => 'canceled',
            'cancel_at_period_end' => false,
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription canceled successfully.',
            'data' => [
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                ],
            ],
        ], 200);
    }

    public function billingHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Transaction::with(['plan', 'subscription'])
            ->where('user_id', $user->id)
            ->latest('id');

        if ($request->filled('status')) {
            $status = strtolower($request->string('status')->value());
            if (in_array($status, ['paid', 'succeeded'], true)) {
                $query->whereIn('status', ['paid', 'succeeded']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('plan_type')) {
            $planType = $request->string('plan_type')->value();
            $query->whereHas('plan', function ($q) use ($planType) {
                $q->where('plan_type', $planType);
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->value();
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('provider_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('plan', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $paginated = $query->paginate($perPage);

        $transactions = $paginated->getCollection()->map(function (Transaction $transaction) {
            $issueDate = $transaction->paid_at ?? $transaction->created_at;

            $cycle = strtolower((string) ($transaction->plan?->billing_cycle ?? 'monthly'));

            $dueDate = match ($cycle) {
                'yearly' => $issueDate?->copy()->addYear(),
                'weekly' => $issueDate?->copy()->addWeek(),
                default => $issueDate?->copy()->addMonth(),
            };

            $formattedBrand = match (strtolower((string) $transaction->card_brand)) {
                'visa' => 'VISA',
                'mastercard' => 'Mastercard',
                'amex', 'american_express', 'american express' => 'AMEX',
                'discover' => 'Discover',
                'jcb' => 'JCB',
                'diners', 'diners_club', 'diners club' => 'Diners Club',
                default => ! empty($transaction->card_brand) ? ucfirst((string) $transaction->card_brand) : null,
            };

            $cardLast4 = $transaction->card_last4;
            if ($formattedBrand && $cardLast4) {
                $paymentMethod = "{$formattedBrand} ************{$cardLast4}";
            } elseif ($formattedBrand) {
                $paymentMethod = $formattedBrand;
            } elseif ($cardLast4) {
                $paymentMethod = "Card ************{$cardLast4}";
            } else {
                $paymentMethod = 'Credit/Debit Card';
            }

            $statusLower = strtolower((string) $transaction->status);
            $statusLabel = match ($statusLower) {
                'succeeded', 'paid' => 'Paid',
                'expired' => 'Expired',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
                'pending' => 'Pending',
                'canceled', 'cancelled' => 'Cancelled',
                default => ucfirst((string) $transaction->status),
            };

            $amount = (float) $transaction->amount;
            $currency = strtolower((string) ($transaction->currency ?? 'usd'));
            $currencySymbol = match ($currency) {
                'usd' => '$',
                'eur' => '€',
                'gbp' => '£',
                default => strtoupper($currency).' ',
            };

            $cycleLabel = match ($cycle) {
                'yearly' => '/ year',
                'weekly' => '/ week',
                default => '/ month',
            };

            $amountFormatted = $currencySymbol.number_format($amount, 2).' '.$cycleLabel;

            $planType = $transaction->plan?->plan_type instanceof PlanType
                ? $transaction->plan->plan_type->value
                : ($transaction->plan?->plan_type ?? 'premium');

            return [
                'id' => $transaction->id,
                'invoice_no' => sprintf('#MU%06d', $transaction->id),
                'transaction_id' => $transaction->provider_transaction_id,
                'status' => $statusLabel,
                'payment_status' => $transaction->status,
                'plan' => $transaction->plan ? [
                    'id' => $transaction->plan->id,
                    'name' => $transaction->plan->name,
                    'plan_type' => $planType,
                    'billing_cycle' => $transaction->plan->billing_cycle,
                    'billing_rate' => (float) $transaction->plan->billing_rate,
                    'badge_color' => $transaction->plan->badge_color,
                ] : null,
                'issue_date' => $issueDate?->format('d M Y h:i A'),
                'issue_date_iso' => $issueDate?->toIso8601String(),
                'due_date' => $dueDate?->format('d M Y h:i A'),
                'due_date_iso' => $dueDate?->toIso8601String(),
                'payment_method' => $paymentMethod,
                'card_brand' => $transaction->card_brand,
                'card_last4' => $transaction->card_last4,
                'amount' => $amount,
                'currency' => $currency,
                'amount_formatted' => trim($amountFormatted),
                'receipt_url' => null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Billing history retrieved successfully.',
            'data' => $transactions,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 200);
    }

    private function featureList(array $features): array
    {
        $labels = PlanFeature::labels();

        $list = [];
        foreach ($labels as $slug => $label) {
            $list[] = ['key' => $label, 'value' => in_array($slug, $features, true)];
        }

        foreach ($features as $slug) {
            if (! isset($labels[$slug])) {
                $list[] = ['key' => str_replace('_', ' ', ucwords($slug, '_')), 'value' => true];
            }
        }

        return $list;
    }

    private function sendOtpToUser(User $user): JsonResponse
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::SUBSCRIPTION->value)
            ->first();

        if ($otpRecord && $otpRecord->expires_at->greaterThan(now())) {
            $remainingSeconds = (int) ceil(now()->diffInSeconds($otpRecord->expires_at, false));

            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
            ], 429);
        }

        $otp = random_int(1000, 9999);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => OtpType::SUBSCRIPTION->value],
            ['otp' => $otp, 'expires_at' => now()->addMinutes(3), 'verified_at' => null],
        );

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
            ->whereIn('status', ['active', 'trialing'])
            ->exists();
    }

    private function verifyOtp(User $user, string $otp): bool
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::SUBSCRIPTION->value)
            ->first();

        if (! $otpRecord || (string) $otpRecord->otp !== $otp) {
            return false;
        }

        if (! $otpRecord->expires_at || now()->greaterThan($otpRecord->expires_at)) {
            return false;
        }

        return true;
    }

    private function invalidOtpResponse(User $user): JsonResponse
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::SUBSCRIPTION->value)
            ->first();
        $expired = $otpRecord && $otpRecord->expires_at && now()->greaterThan($otpRecord->expires_at);

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
                Otp::where('user_id', $user->id)->where('type', OtpType::SUBSCRIPTION->value)->delete();

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
            Otp::where('user_id', $user->id)->where('type', OtpType::SUBSCRIPTION->value)->delete();

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
