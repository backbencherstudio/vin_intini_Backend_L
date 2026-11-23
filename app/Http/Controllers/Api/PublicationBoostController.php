<?php

namespace App\Http\Controllers\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdCampaignResource;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Otp;
use App\Models\Publication;
use App\Models\User;
use App\Notifications\BoostOtpNotification;
use App\Services\Advertising\BoostSettingService;
use App\Services\Billing\ProductBoostPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiErrorException;

class PublicationBoostController extends Controller
{
    public function __construct(
        private readonly ProductBoostPaymentService $boostPaymentService,
        private readonly BoostSettingService $boostSettings,
    ) {}

    /**
     * Preview publication details for boosting plan step.
     */
    public function details(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);
        $user = $request->user();

        if ($user && ($authError = $this->authorizePublicationAction($user, $publication)) instanceof JsonResponse) {
            return $authError;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $publication->id,
                'publication_id' => $publication->publication_id,
                'product_id' => $publication->publication_id,
                'product_name' => $publication->title,
                'title' => $publication->title,
                'slug' => $publication->slug,
                'network_type' => $publication->network_type,
                'publication_type' => $publication->publication_type,
                'attachment_url' => $publication->attachment_url,
                'image_url' => $publication->attachment_url,
                'product_image' => $publication->attachment_url,
                'status' => $publication->status,
                'is_boosted' => (bool) $publication->is_boosted,
                'boost_expires_at' => $publication->boost_expires_at?->toIso8601String(),
                'boost_days_left' => $publication->boost_expires_at && $publication->boost_expires_at->isFuture()
                    ? (int) ceil(now()->diffInSeconds($publication->boost_expires_at) / 86400)
                    : 0,
                'boost_remaining_text' => $publication->boost_expires_at && $publication->boost_expires_at->isFuture()
                    ? (int) ceil(now()->diffInSeconds($publication->boost_expires_at) / 86400) . ' days left'
                    : null,
            ],
        ], 200);
    }

    /**
     * Preview order summary for boosting a Publication.
     */
    public function orderSummary(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);
        $user = $request->user();

        if (($authError = $this->authorizePublicationAction($user, $publication)) instanceof JsonResponse) {
            return $authError;
        }

        $validated = $request->validate([
            'boost_package_id' => ['nullable', 'integer', 'exists:boost_packages,id'],
            'custom_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if (empty($validated['boost_package_id']) && empty($validated['custom_days'])) {
            $defaultPackage = BoostPackage::where('status', 'active')->orderBy('price')->first();
            $validated['boost_package_id'] = $defaultPackage?->id;
        }

        [$package, $durationDays, $amount] = $this->boostPaymentService->resolvePricingAndDuration($validated);
        $orderSummary = $this->boostPaymentService->buildOrderSummary($publication, $package, $durationDays, $amount);

        return response()->json([
            'success' => true,
            'message' => 'Order summary calculated successfully.',
            'data' => [
                'product' => [
                    'id' => $publication->id,
                    'product_id' => $publication->publication_id,
                    'publication_id' => $publication->publication_id,
                    'product_name' => $publication->title,
                    'title' => $publication->title,
                    'slug' => $publication->slug,
                    'image_url' => $publication->attachment_url,
                ],
                'publication' => [
                    'id' => $publication->id,
                    'product_id' => $publication->publication_id,
                    'publication_id' => $publication->publication_id,
                    'product_name' => $publication->title,
                    'title' => $publication->title,
                    'slug' => $publication->slug,
                    'image_url' => $publication->attachment_url,
                ],
                'order_summary' => $orderSummary,
            ],
        ], 200);
    }

    /**
     * Send email verification OTP before charging publication boost payment.
     */
    public function sendOtp(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'checkout_type' => ['nullable', 'string', 'in:stripe,revenuecat'],
            'boost_package_id' => ['nullable', 'integer', 'exists:boost_packages,id'],
            'custom_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $publication = $this->findPublication($id);
        $user = $request->user();

        if (($authError = $this->authorizePublicationAction($user, $publication)) instanceof JsonResponse) {
            return $authError;
        }

        $orderSummary = null;
        if (! empty($validated['boost_package_id']) || ! empty($validated['custom_days'])) {
            [$package, $durationDays, $amount] = $this->boostPaymentService->resolvePricingAndDuration($validated);
            $orderSummary = $this->boostPaymentService->buildOrderSummary($publication, $package, $durationDays, $amount);
        }

        return $this->sendOtpToUser($user, $publication, $orderSummary);
    }

    /**
     * Resend verification OTP for publication boost payment.
     */
    public function resendOtp(Request $request, int|string $id): JsonResponse
    {
        return $this->sendOtp($request, $id);
    }

    /**
     * Purchase a boost for a Publication.
     */
    public function purchase(Request $request, int|string $id): JsonResponse
    {
        $request->merge([
            'checkout_type' => $request->input('checkout_type', 'stripe'),
        ]);

        $validated = $request->validate([
            'checkout_type' => ['required', 'string', 'in:stripe,revenuecat'],
            'boost_package_id' => ['nullable', 'integer', 'exists:boost_packages,id'],
            'custom_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'otp' => ['required_if:checkout_type,stripe', 'nullable', 'digits:4'],
            'payment_method' => ['required_if:checkout_type,stripe', 'string'],
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience'],
            'placements' => ['nullable', 'array'],
            'placements.*' => ['string'],
        ]);

        $publication = $this->findPublication($id);
        $user = $request->user();

        if (($authError = $this->authorizePublicationAction($user, $publication)) instanceof JsonResponse) {
            return $authError;
        }

        // Verify OTP
        if (! $this->verifyOtp($user, (string) $validated['otp'])) {
            return $this->invalidOtpResponse($user);
        }

        // Invalidate OTP after verification
        Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
            ->delete();

        try {
            $result = $this->boostPaymentService->chargeStripeBoost($user, $publication, $validated);

            if (! empty($result['requires_action'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Additional 3D-Secure authentication is required.',
                    'data' => [
                        'requires_action' => true,
                        'client_secret' => $result['client_secret'],
                        'campaign' => new AdCampaignResource($result['campaign']),
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Publication boosted successfully!',
                'data' => [
                    'campaign' => new AdCampaignResource($result['campaign']),
                    'publication' => [
                        'id' => $publication->id,
                        'title' => $publication->title,
                        'is_boosted' => (bool) $publication->fresh()->is_boosted,
                        'boost_expires_at' => $publication->fresh()->boost_expires_at?->toIso8601String(),
                    ],
                ],
            ], 200);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 422);
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

        $publication = $this->findPublication($id);
        $user = $request->user();

        if (($authError = $this->authorizePublicationAction($user, $publication)) instanceof JsonResponse) {
            return $authError;
        }

        $result = $this->boostPaymentService->confirmPendingStripeBoost($publication, $validated['payment_intent_id']);

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment confirmation failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'campaign' => isset($result['campaign']) ? new AdCampaignResource($result['campaign']) : null,
                'publication' => [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'is_boosted' => (bool) $publication->fresh()->is_boosted,
                    'boost_expires_at' => $publication->fresh()->boost_expires_at?->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * List all campaign history for this publication.
     */
    public function campaigns(Request $request, int|string $id): JsonResponse
    {
        $publication = $this->findPublication($id);
        $campaigns = AdCampaign::where('boostable_type', Publication::class)
            ->where('boostable_id', $publication->id)
            ->with(['boostPackage'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => AdCampaignResource::collection($campaigns)->response()->getData(true),
        ], 200);
    }

    /**
     * Locate publication by primary id or scoped publication_id.
     */
    private function findPublication(int|string $id): Publication
    {
        return Publication::query()
            ->where(function ($q) use ($id): void {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('publication_id', (string) $id);
                } else {
                    $q->where('publication_id', $id);
                }
            })
            ->firstOrFail();
    }

    private function authorizePublicationAction(User $user, Publication $publication): ?JsonResponse
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return null;
        }

        if ($publication->creator_id && $publication->creator_id === $user->id) {
            return null;
        }

        if ($user->industry && $user->industry->id === $publication->industry_id) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to boost this publication.',
        ], 403);
    }

    private function sendOtpToUser(User $user, ?Publication $publication = null, ?array $orderSummary = null): JsonResponse
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
            ->latest('id')
            ->first();

        if ($otpRecord && $otpRecord->updated_at && $otpRecord->updated_at->addSeconds(60)->isFuture()) {
            $wait = max(1, (int) ceil(now()->diffInSeconds($otpRecord->updated_at->addSeconds(60))));

            return response()->json([
                'success' => false,
                'message' => "Please wait {$wait} seconds before requesting a new OTP.",
                'data' => ['retry_after' => $wait],
            ], 429);
        }

        $otp = random_int(1000, 9999);
        $expiresAt = now()->addMinutes(3);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => OtpType::BOOST->value],
            ['otp' => $otp, 'expires_at' => $expiresAt, 'verified_at' => null]
        );

        $user->notify(new BoostOtpNotification($otp, $publication?->title, 'publication'));

        $data = [
            'email' => $user->email,
            'requires_verification' => true,
            'cooldown_seconds' => 60,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in_seconds' => 180,
        ];

        if ($publication) {
            $data['product'] = [
                'id' => $publication->id,
                'product_id' => $publication->publication_id,
                'publication_id' => $publication->publication_id,
                'product_name' => $publication->title,
                'title' => $publication->title,
                'slug' => $publication->slug,
                'image_url' => $publication->attachment_url,
            ];
            $data['publication'] = $data['product'];
        }

        if ($orderSummary) {
            $data['order_summary'] = $orderSummary;
        }

        if (app()->environment('local')) {
            $data['debug_otp'] = (string) $otp;
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email. Submit the request again with the OTP and payment method to complete the boost.',
            'data' => $data,
        ], 200);
    }

    private function verifyOtp(User $user, string $otp): bool
    {
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::BOOST->value)
            ->first();

        if (! $otpRecord || (string) $otpRecord->otp !== $otp) {
            return false;
        }

        return $otpRecord->expires_at && ! now()->greaterThan($otpRecord->expires_at);
    }

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
}
