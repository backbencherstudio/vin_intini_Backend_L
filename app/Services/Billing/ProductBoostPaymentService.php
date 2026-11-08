<?php

namespace App\Services\Billing;

use App\Enums\AdCampaignStatus;
use App\Enums\BoostTargetEntity;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\IndustryProduct;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CampaignActivatedNotification;
use App\Services\Advertising\BoostSettingService;
use App\Services\Advertising\ProductBoostProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class ProductBoostPaymentService
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly BoostSettingService $boostSettings,
        private readonly RevenueCatService $revenueCat,
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

        $networkType = $product->network_type;
        $placements = $params['placements'] ?? ['newsfeed', 'product_feed'];
        $paymentMethodId = $params['payment_method'];
        $idempotencyKey = $params['idempotency_key'] ?? null;

        $customer = $this->stripe->getOrCreateCustomer($user);
        $this->stripe->attachPaymentMethod($paymentMethodId, $customer->id);

        $intentArgs = [
            'amountInCents' => (int) round($amount * 100),
            'currency' => 'usd',
            'customerId' => $customer->id,
            'paymentMethodId' => $paymentMethodId,
            'metadata' => [
                'flow' => 'product_boost',
                'user_id' => (string) $user->id,
                'industry_id' => (string) $product->industry_id,
                'product_id' => (string) $product->id,
                'boost_package_id' => $package ? (string) $package->id : 'custom',
                'duration_days' => (string) $durationDays,
            ],
            'confirm' => true,
        ];

        if ($idempotencyKey !== null) {
            $intentArgs['idempotencyKey'] = $idempotencyKey;
        }

        $paymentIntent = $this->stripe->createPaymentIntent(...$intentArgs);

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

        $result = DB::transaction(function () use ($user, $product, $package, $durationDays, $amount, $networkType, $placements, $paymentIntent): array {
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

            ProductBoostProjection::projectFor($product);

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
                'campaign' => $campaign->withoutRelations(),
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        });

        $user->notify(new CampaignActivatedNotification($result['campaign']));

        return $result;
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

        if ($campaign->status !== AdCampaignStatus::PENDING) {
            $status = $campaign->status instanceof \BackedEnum ? $campaign->status->value : (string) $campaign->status;

            return [
                'success' => false,
                'message' => "Campaign cannot be activated from status '{$status}'.",
            ];
        }

        $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);

        if ($paymentIntent->status !== 'succeeded') {
            return [
                'success' => false,
                'message' => "Payment is not completed. Current Stripe status: {$paymentIntent->status}.",
            ];
        }

        return $this->activatePendingStripeCampaign($campaign, $paymentIntent);
    }

    /**
     * Activate a pending campaign associated with a succeeded Stripe payment intent.
     * Can be invoked from either client confirmation or the Stripe webhook handler.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     campaign?: AdCampaign|null,
     *     product?: IndustryProduct|null
     * }
     */
    public function activatePendingStripeCampaign(AdCampaign $campaign, object|array $paymentIntent): array
    {
        return DB::transaction(function () use ($campaign, $paymentIntent): array {
            /** @var AdCampaign|null $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            if (! $lockedCampaign) {
                return [
                    'success' => false,
                    'message' => 'Campaign not found.',
                ];
            }

            if ($lockedCampaign->status === AdCampaignStatus::ACTIVE) {
                return [
                    'success' => true,
                    'message' => 'Campaign is already active.',
                    'campaign' => $lockedCampaign->withoutRelations(),
                    'product' => $lockedCampaign->boostable,
                ];
            }

            if ($lockedCampaign->status !== AdCampaignStatus::PENDING) {
                $status = $lockedCampaign->status instanceof \BackedEnum ? $lockedCampaign->status->value : (string) $lockedCampaign->status;

                return [
                    'success' => false,
                    'message' => "Campaign cannot be activated from status '{$status}'.",
                    'campaign' => $lockedCampaign->withoutRelations(),
                ];
            }

            $product = $lockedCampaign->boostable;
            if (! $product instanceof IndustryProduct) {
                return [
                    'success' => false,
                    'message' => 'Campaign boostable target is invalid.',
                ];
            }

            $baseDate = ($product->is_boosted && $product->boost_expires_at?->isFuture())
                ? $product->boost_expires_at
                : now();

            $startsAt = now();
            $endsAt = $baseDate->copy()->addDays($lockedCampaign->duration_days);

            $lockedCampaign->update([
                'status' => AdCampaignStatus::ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            ProductBoostProjection::projectFor($product);

            $intentId = is_object($paymentIntent) ? $paymentIntent->id : ($paymentIntent['id'] ?? null);
            $cardBrand = data_get($paymentIntent, 'payment_method.card.brand');
            $cardLast4 = data_get($paymentIntent, 'payment_method.card.last4');

            Transaction::updateOrCreate(
                ['provider_transaction_id' => $intentId],
                [
                    'user_id' => $lockedCampaign->user_id,
                    'ad_campaign_id' => $lockedCampaign->id,
                    'amount' => $lockedCampaign->amount_paid,
                    'currency' => 'usd',
                    'card_brand' => $cardBrand,
                    'card_last4' => $cardLast4,
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]
            );

            if ($lockedCampaign->user?->email) {
                $lockedCampaign->user->notify(new CampaignActivatedNotification($lockedCampaign->withoutRelations()));
            }

            return [
                'success' => true,
                'message' => 'Payment confirmed and boost campaign activated successfully.',
                'campaign' => $lockedCampaign->withoutRelations(),
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

        if (! empty($params['custom_days'])) {
            throw ValidationException::withMessages([
                'custom_days' => ['Custom duration boosting is only available for web checkout via Stripe. Mobile app purchases must select a preset boost package.'],
            ]);
        }

        if (! $packageId || ! $transactionId) {
            throw ValidationException::withMessages([
                'package' => ['Boost package and RevenueCat transaction ID are required.'],
            ]);
        }

        $package = BoostPackage::where('status', 'active')
            ->whereIn('target_entity', [BoostTargetEntity::PRODUCT->value, BoostTargetEntity::ALL->value])
            ->find($packageId);
        if (! $package) {
            throw ValidationException::withMessages([
                'boost_package_id' => ['The selected boost package is invalid, inactive, or not eligible for products.'],
            ]);
        }

        // Prevent duplicate crediting of the same store transaction
        $existing = AdCampaign::where('revenuecat_transaction_id', $transactionId)->first();
        if ($existing) {
            if ((int) $existing->user_id !== (int) $user->id || (int) $existing->boostable_id !== (int) $product->id) {
                throw ValidationException::withMessages([
                    'revenuecat_transaction_id' => ['This transaction ID is already associated with another account or target.'],
                ]);
            }

            return [
                'success' => true,
                'message' => 'This purchase has already been activated.',
                'campaign' => $existing,
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        }

        // Server-side verify transaction with RevenueCat
        $appUserId = $this->revenueCat->appUserIdFor($user);
        $expectedProduct = ($params['platform'] ?? null) === 'android'
            ? ($package->revenuecat_store_identifier_android ?? $package->revenuecat_store_identifier_ios)
            : ($package->revenuecat_store_identifier_ios ?? $package->revenuecat_store_identifier_android);

        $verification = $this->revenueCat->verifyTransaction($appUserId, $transactionId, $expectedProduct);
        if (! ($verification['valid'] ?? false)) {
            throw ValidationException::withMessages([
                'revenuecat_transaction_id' => [$verification['error'] ?? 'The provided transaction could not be verified with the app store.'],
            ]);
        }

        $networkType = $product->network_type;
        $placements = $params['placements'] ?? ['newsfeed', 'product_feed'];
        $durationDays = $package->duration_days;
        $amount = (float) $package->price;

        $result = DB::transaction(function () use ($user, $product, $package, $transactionId, $durationDays, $amount, $networkType, $placements): array {
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

            ProductBoostProjection::projectFor($product);

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
                'campaign' => $campaign->withoutRelations(),
                'product' => $product->fresh(['creator', 'industry', 'section', 'category']),
            ];
        });

        $user->notify(new CampaignActivatedNotification($result['campaign']));

        return $result;
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
    public function resolvePricingAndDuration(array $params): array
    {
        if (! empty($params['boost_package_id'])) {
            $package = BoostPackage::where('status', 'active')
                ->whereIn('target_entity', [BoostTargetEntity::PRODUCT->value, BoostTargetEntity::ALL->value])
                ->find($params['boost_package_id']);
            if (! $package) {
                throw ValidationException::withMessages([
                    'boost_package_id' => ['The selected boost package is invalid, inactive, or not eligible for products.'],
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
            $sliderConfig = $this->boostSettings->getSliderConfig();

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

    /**
     * Build an Order Summary payload including product name and estimated impressions.
     *
     * @return array<string, mixed>
     */
    public function buildOrderSummary(IndustryProduct $product, ?BoostPackage $package, int $durationDays, float $amount): array
    {
        $planTitle = $package instanceof BoostPackage
            ? ($package->badge ? "{$package->badge} Boosting" : "{$package->name} Boosting")
            : "Custom Boosting ({$durationDays} Days)";

        if ($package instanceof BoostPackage) {
            $estimatedImpressions = $package->estimated_impressions ?: '5k – 10k Impression';
        } else {
            $sliderConfig = $this->boostSettings->getSliderConfig();
            $minDaily = (int) ($sliderConfig['daily_estimated_impressions_min'] ?? 700);
            $maxDaily = (int) ($sliderConfig['daily_estimated_impressions_max'] ?? 1400);

            $minTotal = round(($durationDays * $minDaily) / 1000, 1);
            $maxTotal = round(($durationDays * $maxDaily) / 1000, 1);

            $minFormatted = ((int) $minTotal == $minTotal) ? (int) $minTotal : $minTotal;
            $maxFormatted = ((int) $maxTotal == $maxTotal) ? (int) $maxTotal : $maxTotal;

            $estimatedImpressions = "{$minFormatted}k – {$maxFormatted}k Impression";
        }

        return [
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'product_image' => $product->image_url,
            'plan_title' => $planTitle,
            'duration_days' => $durationDays,
            'duration_text' => "{$durationDays} Days",
            'estimated_impressions' => "Est. {$estimatedImpressions}",
            'subtotal' => $amount,
            'subtotal_formatted' => '$'.number_format($amount, 2),
            'vat' => 0.00,
            'vat_formatted' => '$0.00',
            'total' => $amount,
            'total_formatted' => '$'.number_format($amount, 2),
            'currency' => 'USD',
        ];
    }
}
