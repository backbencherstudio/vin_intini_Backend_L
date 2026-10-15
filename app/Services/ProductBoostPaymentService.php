<?php

namespace App\Services;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\IndustryProduct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class ProductBoostPaymentService
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    /**
     * Process Stripe card payment for boosting an Industry Product.
     *
     * @param  array{
     *     boost_package_id?: int|null,
     *     custom_days?: int|null,
     *     payment_method: string,
     *     network_type?: string,
     *     placements?: array<string>
     * }  $params
     * @return array{
     *     requires_action: bool,
     *     client_secret?: string|null,
     *     payment_status: string,
     *     campaign: AdCampaign,
     *     product?: IndustryProduct|null
     * }
     *
     * @throws ValidationException|ApiErrorException
     */
    public function chargeStripeBoost(User $user, IndustryProduct $product, array $params): array
    {
        [$package, $durationDays, $amount, $priorityWeight] = $this->resolvePricingAndDuration($params);

        $networkType = $params['network_type'] ?? 'both';
        $placements = $params['placements'] ?? ['marketplace', 'newsfeed'];
        $paymentMethodId = $params['payment_method'];

        $customer = $this->stripe->getOrCreateCustomer($user);
        $this->stripe->attachPaymentMethod($paymentMethodId, $customer->id);

        $paymentIntent = $this->stripe->createPaymentIntent(
            amountInCents: (int) round($amount * 100),
            currency: 'usd',
            customerId: $customer->id,
            paymentMethodId: $paymentMethodId,
            metadata: [
                'flow' => 'product_boost',
                'user_id' => (string) $user->id,
                'industry_id' => (string) $product->industry_id,
                'product_id' => (string) $product->id,
                'boost_package_id' => $package ? (string) $package->id : 'custom',
                'duration_days' => (string) $durationDays,
            ],
            confirm: true,
        );

        if ($paymentIntent->status === 'requires_action') {
            return DB::transaction(function () use ($user, $product, $package, $durationDays, $amount, $networkType, $placements, $paymentIntent): array {
                $campaign = AdCampaign::create([
                    'boostable_type' => IndustryProduct::class,
                    'boostable_id' => $product->id,
                    'user_id' => $user->id,
                    'industry_id' => $product->industry_id,
                    'boost_package_id' => $package?->id,
                    'ad_type' => 'product',
                    'network_type' => $networkType,
                    'placements' => $placements,
                    'amount_paid' => $amount,
                    'currency' => 'USD',
                    'duration_days' => $durationDays,
                    'status' => AdCampaignStatus::PENDING,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_platform' => 'stripe',
                ]);

                Transaction::create([
                    'provider_transaction_id' => $paymentIntent->id,
                    'user_id' => $user->id,
                    'ad_campaign_id' => $campaign->id,
                    'amount' => $amount,
                    'currency' => 'usd',
                    'status' => 'pending',
                ]);

                return [
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_status' => $paymentIntent->status,
                    'campaign' => $campaign,
                ];
            });
        }

        if ($paymentIntent->status !== 'succeeded') {
            throw ValidationException::withMessages([
                'payment' => ["Payment could not be processed (status: {$paymentIntent->status})."],
            ]);
        }

        return DB::transaction(function () use ($user, $product, $package, $durationDays, $amount, $networkType, $placements, $paymentIntent, $priorityWeight): array {
            $baseDate = ($product->is_boosted && $product->boost_expires_at?->isFuture())
                ? $product->boost_expires_at
                : now();

            $startsAt = now();
            $endsAt = $baseDate->copy()->addDays($durationDays);

            $campaign = AdCampaign::create([
                'boostable_type' => IndustryProduct::class,
                'boostable_id' => $product->id,
                'user_id' => $user->id,
                'industry_id' => $product->industry_id,
                'boost_package_id' => $package?->id,
                'ad_type' => 'product',
                'network_type' => $networkType,
                'placements' => $placements,
                'amount_paid' => $amount,
                'currency' => 'USD',
                'duration_days' => $durationDays,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AdCampaignStatus::ACTIVE,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_platform' => 'stripe',
            ]);

            $product->update([
                'is_boosted' => true,
                'boost_expires_at' => $endsAt,
                'boost_score' => $priorityWeight,
            ]);

            Transaction::create([
                'provider_transaction_id' => $paymentIntent->id,
                'user_id' => $user->id,
                'ad_campaign_id' => $campaign->id,
                'amount' => $amount,
                'currency' => 'usd',
                'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
                'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);

            return [
                'requires_action' => false,
                'client_secret' => null,
                'payment_status' => 'succeeded',
                'campaign' => $campaign,
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        });
    }

    /**
     * Confirm a pending 3D-Secure Stripe payment intent for a boost campaign.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     campaign?: AdCampaign|null,
     *     product?: IndustryProduct|null
     * }
     */
    public function confirmStripePayment(User $user, IndustryProduct $product, string $paymentIntentId): array
    {
        $campaign = AdCampaign::where('stripe_payment_intent_id', $paymentIntentId)
            ->where('boostable_type', IndustryProduct::class)
            ->where('boostable_id', $product->id)
            ->first();

        if (! $campaign) {
            return [
                'success' => false,
                'message' => 'Boost campaign not found for this payment intent.',
            ];
        }

        if ($campaign->status === AdCampaignStatus::ACTIVE) {
            return [
                'success' => true,
                'message' => 'Campaign is already active.',
                'campaign' => $campaign,
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        }

        $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);

        if ($paymentIntent->status !== 'succeeded') {
            return [
                'success' => false,
                'message' => "Payment is not completed. Current Stripe status: {$paymentIntent->status}.",
            ];
        }

        return DB::transaction(function () use ($campaign, $product, $paymentIntent): array {
            $baseDate = ($product->is_boosted && $product->boost_expires_at?->isFuture())
                ? $product->boost_expires_at
                : now();

            $startsAt = now();
            $endsAt = $baseDate->copy()->addDays($campaign->duration_days);

            $priorityWeight = $campaign->boostPackage?->priority_weight ?? 1;

            $campaign->update([
                'status' => AdCampaignStatus::ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $product->update([
                'is_boosted' => true,
                'boost_expires_at' => $endsAt,
                'boost_score' => $priorityWeight,
            ]);

            Transaction::updateOrCreate(
                ['provider_transaction_id' => $paymentIntent->id],
                [
                    'user_id' => $campaign->user_id,
                    'ad_campaign_id' => $campaign->id,
                    'amount' => $campaign->amount_paid,
                    'currency' => 'usd',
                    'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
                    'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]
            );

            return [
                'success' => true,
                'message' => 'Payment confirmed and boost campaign activated successfully.',
                'campaign' => $campaign->fresh(),
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        });
    }

    /**
     * Activate a boost campaign purchased via RevenueCat in-app purchase (iOS/Android).
     *
     * @param  array{
     *     boost_package_id: int,
     *     revenuecat_transaction_id: string,
     *     network_type?: string,
     *     placements?: array<string>
     * }  $params
     * @return array{
     *     success: bool,
     *     message: string,
     *     campaign: AdCampaign,
     *     product: IndustryProduct
     * }
     *
     * @throws ValidationException
     */
    public function activateRevenueCatBoost(User $user, IndustryProduct $product, array $params): array
    {
        $packageId = $params['boost_package_id'] ?? null;
        $transactionId = $params['revenuecat_transaction_id'] ?? null;

        if (! $packageId || ! $transactionId) {
            throw ValidationException::withMessages([
                'package' => ['Boost package and RevenueCat transaction ID are required.'],
            ]);
        }

        $package = BoostPackage::where('status', 'active')->find($packageId);
        if (! $package) {
            throw ValidationException::withMessages([
                'boost_package_id' => ['The selected boost package is invalid or inactive.'],
            ]);
        }

        // Prevent duplicate crediting of the same store transaction
        $existing = AdCampaign::where('revenuecat_transaction_id', $transactionId)->first();
        if ($existing) {
            return [
                'success' => true,
                'message' => 'This purchase has already been activated.',
                'campaign' => $existing,
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        }

        $networkType = $params['network_type'] ?? 'both';
        $placements = $params['placements'] ?? ['marketplace', 'newsfeed'];
        $durationDays = $package->duration_days;
        $amount = (float) $package->price;
        $priorityWeight = $package->priority_weight;

        return DB::transaction(function () use ($user, $product, $package, $transactionId, $durationDays, $amount, $networkType, $placements, $priorityWeight): array {
            $baseDate = ($product->is_boosted && $product->boost_expires_at?->isFuture())
                ? $product->boost_expires_at
                : now();

            $startsAt = now();
            $endsAt = $baseDate->copy()->addDays($durationDays);

            $campaign = AdCampaign::create([
                'boostable_type' => IndustryProduct::class,
                'boostable_id' => $product->id,
                'user_id' => $user->id,
                'industry_id' => $product->industry_id,
                'boost_package_id' => $package->id,
                'ad_type' => 'product',
                'network_type' => $networkType,
                'placements' => $placements,
                'amount_paid' => $amount,
                'currency' => 'USD',
                'duration_days' => $durationDays,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AdCampaignStatus::ACTIVE,
                'revenuecat_transaction_id' => $transactionId,
                'payment_platform' => 'revenuecat',
            ]);

            $product->update([
                'is_boosted' => true,
                'boost_expires_at' => $endsAt,
                'boost_score' => $priorityWeight,
            ]);

            Transaction::create([
                'provider_transaction_id' => $transactionId,
                'user_id' => $user->id,
                'ad_campaign_id' => $campaign->id,
                'amount' => $amount,
                'currency' => 'usd',
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Boost campaign activated successfully via mobile store purchase.',
                'campaign' => $campaign,
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        });
    }

    /**
     * Resolve pricing and duration from preset package or custom slider days.
     *
     * @param  array{
     *     boost_package_id?: int|null,
     *     custom_days?: int|null
     * }  $params
     * @return array{0: BoostPackage|null, 1: int, 2: float, 3: int}
     *
     * @throws ValidationException
     */
    private function resolvePricingAndDuration(array $params): array
    {
        if (! empty($params['boost_package_id'])) {
            $package = BoostPackage::where('status', 'active')->find($params['boost_package_id']);
            if (! $package) {
                throw ValidationException::withMessages([
                    'boost_package_id' => ['The selected boost package is invalid or inactive.'],
                ]);
            }

            return [
                $package,
                (int) $package->duration_days,
                (float) $package->price,
                (int) $package->priority_weight,
            ];
        }

        if (! empty($params['custom_days'])) {
            $customDays = (int) $params['custom_days'];
            $sliderConfig = config('boosting.custom_slider', []);

            $minDays = (int) ($sliderConfig['min_days'] ?? 3);
            $maxDays = (int) ($sliderConfig['max_days'] ?? 60);
            $dailyRate = (float) ($sliderConfig['daily_rate'] ?? 3.00);

            if ($customDays < $minDays || $customDays > $maxDays) {
                throw ValidationException::withMessages([
                    'custom_days' => ["Custom duration must be between {$minDays} and {$maxDays} days."],
                ]);
            }

            $amount = round($customDays * $dailyRate, 2);

            return [
                null,
                $customDays,
                $amount,
                1,
            ];
        }

        throw ValidationException::withMessages([
            'package' => ['Please select a boost package or specify custom duration days.'],
        ]);
    }
}
