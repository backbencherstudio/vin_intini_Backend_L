<?php

namespace App\Http\Controllers\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\BoostTargetEntity;
use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoostPackageResource;
use App\Http\Resources\IndustryProductResource;
use App\Mail\BoostOtpMail;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\BoostPackage;
use App\Models\IndustryProduct;
use App\Models\Otp;
use App\Models\User;
use App\Services\BoostSettingService;
use App\Services\ProductBoostPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class ProductBoostController extends Controller
{
    public function __construct(
        private readonly ProductBoostPaymentService $boostPaymentService,
        private readonly BoostSettingService $boostSettings,
    ) {}

    /**
     * Get active boost packages and custom slider configuration for advertisers.
     */
    public function packages(Request $request): JsonResponse
    {
        $target = $request->input('target_entity', 'product');

        $packages = BoostPackage::query()
            ->where('status', 'active')
            ->whereIn('target_entity', [$target, BoostTargetEntity::ALL->value])
            ->orderByDesc('priority_weight')
            ->orderBy('price')
            ->get();

        $customSliderConfig = $this->boostSettings->getSliderConfig();

        return response()->json([
            'success' => true,
            'data' => [
                'stripe_public_key' => config('services.stripe.key'),
                'preset_packages' => BoostPackageResource::collection($packages),
                'custom_slider' => $customSliderConfig,
            ],
        ], 200);
    }

    /**
     * Send email verification OTP before charging boost payment (identical to Subscription flow).
     */
    public function sendOtp(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'checkout_type' => ['required', 'string', 'in:stripe,revenuecat'],
            'boost_package_id' => ['nullable', 'integer', 'exists:boost_packages,id'],
            'custom_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if ($validated['checkout_type'] === 'revenuecat') {
            return response()->json([
                'success' => false,
                'message' => 'This boost package is purchased in the app store via RevenueCat. Complete the purchase in the app and your boost will activate automatically.',
            ], 422);
        }

        $product = IndustryProduct::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeProductAction($user, $product)) {
            return $authError;
        }

        return $this->sendOtpToUser($user);
    }

    /**
     * Purchase a boost for an Industry Product.
     * Supports both Web (Stripe Elements in-modal card payment with email OTP) and Mobile (RevenueCat IAP).
     */
    public function purchase(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'checkout_type' => ['required', 'string', 'in:stripe,revenuecat'],
            'boost_package_id' => ['required_if:checkout_type,revenuecat', 'nullable', 'integer', 'exists:boost_packages,id'],
            'custom_days' => ['prohibited_if:checkout_type,revenuecat', 'nullable', 'integer', 'min:1', 'max:365'],
            'otp' => ['required_if:checkout_type,stripe', 'nullable', 'digits:4'],
            'payment_method' => ['required_if:checkout_type,stripe', 'string'],
            'revenuecat_transaction_id' => ['required_if:checkout_type,revenuecat', 'string'],
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience,both'],
            'placements' => ['nullable', 'array'],
            'placements.*' => ['string'],
        ], [
            'custom_days.prohibited_if' => 'Custom duration boosting is only available for web checkout via Stripe. Mobile app purchases must select a preset boost package.',
            'boost_package_id.required_if' => 'Please select a preset boost package for mobile app purchases.',
        ]);

        $product = IndustryProduct::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeProductAction($user, $product)) {
            return $authError;
        }

        try {
            if ($validated['checkout_type'] === 'revenuecat') {
                $result = $this->boostPaymentService->activateRevenueCatBoost($user, $product, $validated);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'campaign' => $result['campaign'],
                        'product' => new IndustryProductResource($result['product']),
                    ],
                ], 201);
            }

            // Web Stripe flow: verify OTP first (same as SubscriptionController)
            if (! $this->verifyOtp($user, (string) ($validated['otp'] ?? ''))) {
                return $this->invalidOtpResponse($user);
            }

            // Stripe Elements direct card payment
            $result = $this->boostPaymentService->chargeStripeBoost($user, $product, $validated);

            // Clean up used OTP
            Otp::where('user_id', $user->id)->where('type', OtpType::BOOST->value)->delete();

            if ($result['requires_action']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Additional authentication is required to complete your payment.',
                    'data' => [
                        'requires_action' => true,
                        'payment_intent_client_secret' => $result['client_secret'],
                        'payment_status' => $result['payment_status'],
                        'campaign_id' => $result['campaign']->id,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product boosted successfully! Your campaign is now live.',
                'data' => [
                    'requires_action' => false,
                    'campaign' => $result['campaign'],
                    'product' => new IndustryProductResource($result['product']),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe payment error: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process boost payment. Please try again.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm a 3D-Secure Stripe payment intent after client-side modal completion.
     */
    public function confirmPayment(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        $product = IndustryProduct::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeProductAction($user, $product)) {
            return $authError;
        }

        try {
            $result = $this->boostPaymentService->confirmStripePayment($user, $product, $validated['payment_intent_id']);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'campaign' => $result['campaign'],
                    'product' => new IndustryProductResource($result['product']),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm boost payment.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List all campaigns and performance metrics for a specific product.
     */
    public function campaigns(Request $request, int|string $id): JsonResponse
    {
        $product = IndustryProduct::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeProductAction($user, $product)) {
            return $authError;
        }

        $campaigns = $product->campaigns()
            ->with('boostPackage')
            ->latest('id')
            ->get();

        $metrics = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'total_impressions' => (int) $campaigns->sum('total_impressions'),
            'total_clicks' => (int) $campaigns->sum('total_clicks'),
            'total_spend' => (float) $campaigns->sum('amount_paid'),
            'current_is_boosted' => $product->isBoosted(),
            'boost_expires_at' => $product->boost_expires_at?->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'campaigns' => $campaigns,
            ],
        ], 200);
    }

    /**
     * Pause an active boost campaign and freeze remaining time.
     */
    public function pauseCampaign(Request $request, int|string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeCampaignAction($user, $campaign)) {
            return $authError;
        }

        if (! $campaign->pause()) {
            return response()->json([
                'success' => false,
                'message' => 'Only active campaigns can be paused.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign paused successfully. Remaining time has been frozen.',
            'data' => [
                'campaign' => $campaign->fresh(),
            ],
        ], 200);
    }

    /**
     * Resume a paused boost campaign and extend expiration date by the exact paused duration.
     */
    public function resumeCampaign(Request $request, int|string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $user = $request->user();

        if ($authError = $this->authorizeCampaignAction($user, $campaign)) {
            return $authError;
        }

        if (! $campaign->resume()) {
            return response()->json([
                'success' => false,
                'message' => 'Only paused campaigns can be resumed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign resumed successfully. Expiration date extended by paused duration.',
            'data' => [
                'campaign' => $campaign->fresh(),
            ],
        ], 200);
    }

    /**
     * Check if user is authorized to manage a specific campaign.
     */
    private function authorizeCampaignAction(User $user, AdCampaign $campaign): ?JsonResponse
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return null;
        }

        if ($campaign->user_id && $campaign->user_id === $user->id) {
            return null;
        }

        if ($user->industry && $user->industry->id === $campaign->industry_id) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to manage this campaign.',
        ], 403);
    }

    /**
     * Send 4-digit OTP to user's email.
     */
    private function sendOtpToUser(User $user): JsonResponse
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
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
            ['user_id' => $user->id, 'type' => OtpType::BOOST->value],
            ['otp' => $otp, 'expires_at' => now()->addMinutes(3), 'verified_at' => null],
        );

        Mail::to($user->email)->queue(new BoostOtpMail($otp));

        $data = [
            'email' => $user->email,
            'requires_verification' => true,
        ];

        if (app()->environment('local')) {
            $data['debug_otp'] = (string) $otp;
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email. Submit the request again with the OTP and payment method to complete the boost.',
            'data' => $data,
        ], 200);
    }

    /**
     * Verify if the provided OTP matches and is not expired.
     */
    private function verifyOtp(User $user, string $otp): bool
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
            ->first();

        if (! $otpRecord || (string) $otpRecord->otp !== $otp) {
            return false;
        }

        if (! $otpRecord->expires_at || now()->greaterThan($otpRecord->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Standard invalid/expired OTP response matching SubscriptionController.
     */
    private function invalidOtpResponse(User $user): JsonResponse
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
            ->first();
        $expired = $otpRecord && $otpRecord->expires_at && now()->greaterThan($otpRecord->expires_at);

        return response()->json([
            'success' => false,
            'message' => $expired ? 'OTP expired. Please request a new one.' : 'Invalid OTP.',
            'data' => ['can_resend_otp' => $expired],
        ], 400);
    }

    /**
     * Check if user is authorized to perform advertising actions on this product.
     */
    private function authorizeProductAction(User $user, IndustryProduct $product): ?JsonResponse
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return null;
        }

        if ($product->creator_id && $product->creator_id === $user->id) {
            return null;
        }

        if ($user->industry && $user->industry->id === $product->industry_id) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to boost this product.',
        ], 403);
    }

    /**
     * Batch log verified IAB viewable impressions from client viewport detection.
     */
    public function recordImpressions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_ids' => ['required', 'array', 'min:1'],
            'campaign_ids.*' => ['integer', 'exists:ad_campaigns,id'],
            'placement' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth('api')->user() ?? $request->user();
        $placement = $validated['placement'] ?? 'newsfeed';
        $ip = $request->ip();
        $today = today();

        $campaigns = AdCampaign::whereIn('id', $validated['campaign_ids'])
            ->where('status', AdCampaignStatus::ACTIVE)
            ->with('boostable')
            ->get();

        $count = 0;
        foreach ($campaigns as $campaign) {
            AdImpression::create([
                'ad_campaign_id' => $campaign->id,
                'boostable_type' => $campaign->boostable_type,
                'boostable_id' => $campaign->boostable_id,
                'user_id' => $user?->id,
                'placement' => $placement,
                'ip_address' => $ip,
                'viewed_date' => $today,
            ]);

            $campaign->increment('total_impressions');

            if ($campaign->boostable && method_exists($campaign->boostable, 'increment')) {
                $campaign->boostable->increment('impressions_count');
            }

            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Impressions recorded successfully.',
            'recorded_count' => $count,
        ]);
    }

    /**
     * Record a verified click on an ad campaign and return destination details.
     */
    public function recordClick(Request $request, int|string $id): JsonResponse
    {
        $campaign = AdCampaign::with('boostable')->find($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        $user = auth('api')->user() ?? $request->user();
        $placement = $request->input('placement', 'newsfeed');
        $ip = $request->ip();

        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => $campaign->boostable_type,
            'boostable_id' => $campaign->boostable_id,
            'user_id' => $user?->id,
            'placement' => $placement,
            'ip_address' => $ip,
        ]);

        $campaign->increment('total_clicks');

        if ($campaign->boostable && method_exists($campaign->boostable, 'increment')) {
            $campaign->boostable->increment('clicks_count');
        }

        $destinationUrl = method_exists($campaign->boostable, 'getAdDestinationUrl')
            ? $campaign->boostable->getAdDestinationUrl()
            : ($campaign->boostable->product_url ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Click recorded successfully.',
            'data' => [
                'ad_campaign_id' => $campaign->id,
                'total_clicks' => $campaign->fresh()->total_clicks,
                'destination_url' => $destinationUrl,
            ],
        ]);
    }
}
