<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\Industry;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\RegisterOtpNotification;
use App\Services\Auth\ChallengeService;
use App\Services\Auth\SessionIssuanceService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class AuthController extends Controller
{
    public function __construct(
        protected SessionIssuanceService $sessionService,
        protected ChallengeService $challengeService,
    ) {}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();

        $user = User::withTrashed()->where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (($statusResponse = $this->sessionService->checkAccountStatus($user)) instanceof JsonResponse) {
            return $statusResponse;
        }

        // Save the FCM token before the 2FA check so it persists even when
        // authentication pauses for the two-factor code.
        if (! empty($credentials['fcm_token'])) {
            FcmToken::assignTo($user, $credentials['fcm_token']);
        }

        // Check if 2FA is enabled and confirmed
        if ($user->two_factor_confirmed_at) {
            $challengeToken = $this->challengeService->createChallenge($user, '2fa', 5);

            return response()->json([
                'status' => '2fa_required',
                'two_factor_enabled' => true,
                'email' => $user->email,
                'challenge_token' => $challengeToken,
                'message' => 'Two-factor authentication is required. Please provide your code.',
            ], 200);
        }

        if ($user->hasRole('user') && ! $user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email with OTP before login.',
            ], 403);
        }

        // Attempt login (JWT token) — only pass actual auth columns,
        // fcm_token/device fields are handled separately below.
        /** @var string|false $token */
        $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = auth('api')->user();

        return $this->sessionService->respondWithSession($user, $token);
    }

    public function me()
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $industry = Industry::where('created_by', $user->id)
            ->withCount('followers')
            ->first();

        $user->load(['roles', 'profile.currentPosition', 'educations.institution']);

        $user->educations->sortByDesc('id')->first();

        $subscription = $user->activeSubscription()
            ?? $user->subscriptions()->with('plan')->latest()->first();
        $isSubscribed = (bool) $subscription?->isActive();

        return response()->json([
            'success' => true,
            'is_onboarding' => (bool) $user->profile,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'profile_image_url' => $user->profile_image_url,
                'cover_image_url' => $user->cover_image_url,
                'role' => $user->roles->pluck('name')->implode(', '),
                'member_since' => $user->created_at->toDateString(),

                'two_factor_enabled' => (bool) $user->two_factor_confirmed_at,
                'recovery_email' => $user->recovery_email,
                'recovery_email_verified' => (bool) $user->recovery_email_verified_at,

                'profile' => $user->profile ? [
                    'privacy_profile_activity' => $user->profile->privacy_profile_activity,
                    'privacy_profile_visibility' => $user->profile->privacy_profile_visibility,
                    'address' => $user->profile->address,
                    'country' => $user->profile->country,

                    'state' => $user->profile->state ? [
                        'id' => $user->profile->state->id,
                        'name' => $user->profile->state->name,
                    ] : null,

                    'postal_code' => $user->profile->postal_code,
                    'profession' => $user->profile->profession,

                    'interests' => $user->profile->interests,

                    'skills' => Skill::query()
                        ->whereIn('id', $user->profile->skills_id ?? [])
                        ->orderBy('name')
                        ->pluck('name')
                        ->values(),

                    'current_position' => $user->profile->currentPosition ? [
                        'id' => $user->profile->currentPosition->id,
                        'company_name' => $user->profile->currentPosition->name,
                    ] : null,

                    'institution' => $user->profile->currentInstitute ? [
                        'id' => $user->profile->currentInstitute->id,
                        'name' => $user->profile->currentInstitute->name,
                    ] : null,

                    'about' => $user->profile->about,
                ] : null,
            ],
            'subscription' => [
                'is_subscribed' => $isSubscribed,
                'plan_id' => $isSubscribed ? $subscription->plan_id : null,
                'plan_name' => $isSubscribed ? $subscription->plan?->name : null,
                'plan_type' => $isSubscribed ? $subscription->plan?->plan_type : null,
                'status' => $isSubscribed ? $subscription->status : null,
                'features' => $isSubscribed ? ($subscription->plan?->features ?? []) : [],
                'expires_at' => $isSubscribed ? $subscription->current_period_end?->toIso8601String() : null,
                'will_renew' => $isSubscribed ? ! $subscription->cancel_at_period_end : null,
            ],
            'industry' => [
                'can_create_company_page' => $user->canCreateCompanyPage(),
                'has_company' => (bool) $industry,
                'total_followers' => (int) ($industry?->followers_count ?? 0),
                'company' => $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'industry' => $industry->industry,
                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,
                    'tagline' => $industry->tagline,
                    'description' => $industry->description,
                    'logo' => $industry->logo,
                    'cover_image' => $industry->cover_image,
                    'followers_count' => (int) $industry->followers_count,
                ] : null,
            ],
        ]);
    }

    public function logout()
    {
        try {
            $user = auth('api')->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 401);
            }

            if ($fcmToken = request('fcm_token')) {
                $user->fcmTokens()->where('fcm_token', $fcmToken)->delete();
            }

            $payload = auth('api')->payload();
            if ($payload) {
                $tokenId = $payload->get('jti');
                LoginActivity::where('token_id', $tokenId)
                    ->where('user_id', $user->id)
                    ->update(['is_active' => 0]);
            }

            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        }
    }

    public function refresh()
    {
        $token = auth('api')->getToken();

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Token not provided'], 401);
        }

        try {
            $oldJti = $this->getJtiFromToken($token);

            $loginActivity = LoginActivity::where('token_id', $oldJti)->first();

            if (! $loginActivity || ! $loginActivity->is_active) {
                return response()->json(['success' => false, 'message' => 'Session is inactive. Please login again.'], 401);
            }

            $rollingMinutes = config('jwt.rolling_window');

            if ($loginActivity->updated_at->lt(now()->subMinutes($rollingMinutes))) {
                $loginActivity->update(['is_active' => false]);

                return response()->json(['success' => false, 'message' => 'Session expired. Please login again.'], 401);
            }

            $newToken = auth('api')->refresh();
            $user = auth('api')->setToken($newToken)->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 401);
            }

            $newJti = auth('api')->setToken($newToken)->getPayload()->get('jti');

            $loginActivity->update([
                'token_id' => $newJti,
                'updated_at' => now(),
                'is_active' => true,
            ]);

            return $this->respondWithToken($newToken, $user);
        } catch (TokenBlacklistedException) {
            return response()->json(['success' => false, 'message' => 'Session blacklisted. Please login again.'], 401);
        } catch (\Exception) {
            return response()->json(['success' => false, 'message' => 'Invalid token or session error'], 401);
        }
    }

    private function getJtiFromToken(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode($parts[1]), true);

            return $payload['jti'] ?? null;
        } catch (\Exception) {
            return null;
        }
    }

    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        $roleName = $user->getRoleNames()->first();
        $user->makeHidden('roles');
        $user->role = $roleName;

        return response()->json([
            'success' => true,
            'is_onboarding' => $user->profile()->exists(),
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $otp = random_int(1000, 9999);
            $role = Role::where('name', 'user')->first();

            $user = User::where('email', $request->email)->first();

            if ($user) {

                if ($user->is_verified) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This email is already registered.',
                    ], 409);
                }

                $user->update([
                    'password' => Hash::make($request->password),
                    'is_verified' => false,
                    'terms_accepted_at' => now(),
                ]);

                Otp::updateOrCreate(
                    ['user_id' => $user->id, 'type' => OtpType::REGISTRATION->value],
                    ['otp' => $otp, 'expires_at' => now()->addMinutes(3), 'verified_at' => null],
                );

                if ($role && ! $user->hasRole('user')) {
                    $user->assignRole($role);
                }

                $user->notify(new RegisterOtpNotification($otp));

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'OTP resent to your email.',
                ], 200);
            }

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_verified' => false,
                'terms_accepted_at' => now(),
            ]);

            Otp::updateOrCreate(
                ['user_id' => $user->id, 'type' => OtpType::REGISTRATION->value],
                ['otp' => $otp, 'expires_at' => now()->addMinutes(3), 'verified_at' => null],
            );

            if ($role) {
                $user->assignRole($role);
            }

            $user->notify(new RegisterOtpNotification($otp));

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registration successful. OTP sent to your email.',
            ], 201);
        } catch (\Exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Registration failed.',

            ], 500);
        }
    }

    public function verifyRegisterOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (($statusResponse = $this->sessionService->checkAccountStatus($user)) instanceof JsonResponse) {
            return $statusResponse;
        }

        if ($user->is_verified) {
            return response()->json([
                'status' => false,
                'message' => 'Email is already verified. Please sign in.',
            ], 400);
        }

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::REGISTRATION->value)
            ->first();

        if (! $otpRecord || (string) $otpRecord->otp !== (string) $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (! $otpRecord->expires_at || now()->gt($otpRecord->expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'can_resend_otp' => true,
            ], 400);
        }

        return DB::transaction(function () use ($user, $otpRecord) {
            $user->forceFill([
                'is_verified' => true,
            ])->save();

            $otpRecord->delete();

            if ($user->is_suspended) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }

            return $this->sessionService->respondWithSession($user, null, [
                'message' => 'Email verified successfully.',
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        });
    }

    public function resendRegisterOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified.',
            ], 200);
        }

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::REGISTRATION->value)
            ->first();

        if ($otpRecord?->expires_at && now()->lt($otpRecord->expires_at)) {

            $remainingSeconds = (int) ceil(now()->diffInSeconds($otpRecord->expires_at, false));

            return response()->json([
                'status' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
            ], 429);
        }

        try {
            $otp = random_int(1000, 9999);

            Otp::updateOrCreate(
                ['user_id' => $user->id, 'type' => OtpType::REGISTRATION->value],
                ['otp' => $otp, 'expires_at' => now()->addMinutes(3), 'verified_at' => null],
            );

            $user->notify(new RegisterOtpNotification($otp));

            return response()->json([
                'status' => true,
                'message' => 'OTP resent to your email.',
            ], 200);
        } catch (\Exception) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to resend OTP.',
            ], 500);
        }
    }
}
