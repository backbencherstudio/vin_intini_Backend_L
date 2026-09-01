<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegisterOtpMail;
use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
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

            // Trigger the Failed event to log the failed login activity--------------
            // if ($user) {
            //     event(new Failed('api', $user, $credentials));
            // }
            // ------------------------------------------------------------------------
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if the user is soft-deleted (account scheduled for deletion)
        if ($user->trashed()) {
            $token = auth('api')->login($user);

            $deletionLog = DeletedAccountLog::where('user_id', $user->id)->latest()->first();

            $daysRemaining = 0;
            if ($deletionLog) {
                $seconds = now()->diffInSeconds($deletionLog->permanent_delete_at, false);
                $daysRemaining = ceil($seconds / (60 * 60 * 24));
            }

            if ($daysRemaining <= 0) {
                $daysRemaining = 1;
            }

            return response()->json([
                'status' => 'pending_deletion',
                'is_onboarding' => $user->profile ? true : false,
                'days_left' => (int) $daysRemaining,
                'name' => $user->first_name . ' ' . $user->last_name,
                'message' => "Your account is scheduled for deletion in {$daysRemaining} days. Would you like to restore it?",
                'token' => $token,
            ], 200);
        }

        // Check if 2FA is enabled and confirmed
        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'status' => '2fa_required',
                'two_factor_enabled' => true,
                'email' => $user->email,
                'message' => 'Two-factor authentication is required. Please provide your code.',
            ], 200);
        }

        // if (! $user->is_verified) {
        if ($user->hasRole('user') && ! $user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email with OTP before login.',
            ], 403);
        }

        // Attempt login (JWT token) — only pass actual auth columns,
        // fcm_token/device fields are handled separately below.
        if (! $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = auth('api')->user();

        if (! empty($credentials['fcm_token'])) {
            $user->fcmTokens()->where('fcm_token', '!=', $credentials['fcm_token'])->delete();
            $user->fcmTokens()->updateOrCreate(
                ['user_id' => $user->id],
                ['fcm_token' => $credentials['fcm_token']]
            );
        }
        // -----------------------------------------
        // Trigger the Login event to log the successful login activity
        $payload = auth('api')->setToken($token)->getPayload(); // Get the payload of the token
        $tokenId = $payload->get('jti'); // Get the token ID (jti) from the payload
        request()->merge(['current_token_id' => $tokenId]); // Merge the token ID into the request for later use
        event(new Login('api', $user, false)); // Trigger the Login event to log the successful login activity
        // -----------------------------------------

        return $this->respondWithToken($token, $user);
    }

    public function me()
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $user->load(['roles', 'profile.currentPosition', 'educations.institution']);

        $latestEducation = $user->educations->sortByDesc('id')->first();

        $subscription = $user->subscriptions()->with('plan')->latest()->first();
        $isSubscribed = (bool) $subscription?->isActive();

        return response()->json([
            'success' => true,
            'is_onboarding' => $user->profile ? true : false,
            'subscription' => [
                'is_subscribed' => $isSubscribed,
                'plan_id' => $isSubscribed ? $subscription->plan_id : null,
                'plan_name' => $isSubscribed ? $subscription->plan?->name : null,
                'status' => $isSubscribed ? $subscription->status : null,
                'features' => $isSubscribed ? ($subscription->plan?->features ?? []) : [],
                'expires_at' => $isSubscribed ? $subscription->current_period_end?->toIso8601String() : null,
                'will_renew' => $isSubscribed ? ! $subscription->cancel_at_period_end : null,
            ],
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

                'two_factor_enabled' => $user->two_factor_confirmed_at ? true : false,
                'recovery_email' => $user->recovery_email,
                'recovery_email_verified' => $user->recovery_email_verified_at ? true : false,
                // 'recovery_email_pending' => !$user->recovery_email_verified_at && $user->recovery_email ? true : false,

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
        ]);
    }

    public function logout()
    {
        try {
            $user = auth('api')->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 401);
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        }
    }


    public function refresh()
    {
        $token = auth('api')->getToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token not provided'], 401);
        }

        try {
            $oldJti = $this->getJtiFromToken($token);

            $loginActivity = LoginActivity::where('token_id', $oldJti)->first();

            if (!$loginActivity || !$loginActivity->is_active) {
                return response()->json(['success' => false, 'message' => 'Session is inactive. Please login again.'], 401);
            }

            $rollingMinutes = config('jwt.rolling_window');

            if ($loginActivity->updated_at->lt(now()->subMinutes($rollingMinutes))) {
                $loginActivity->update(['is_active' => false]);
                return response()->json(['success' => false, 'message' => 'Session expired. Please login again.'], 401);
            }

            $newToken = auth('api')->refresh();
            $user = auth('api')->setToken($newToken)->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 401);
            }

            $newJti = auth('api')->setToken($newToken)->getPayload()->get('jti');

            $loginActivity->update([
                'token_id' => $newJti,
                'updated_at' => now(),
                'is_active' => true,
            ]);

            return $this->respondWithToken($newToken, $user);
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return response()->json(['success' => false, 'message' => 'Session blacklisted. Please login again.'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid token or session error'], 401);
        }
    }

    private function getJtiFromToken($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) return null;
            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload['jti'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }


    // public function refresh()
    // {
    //     try {
    //         $token = auth('api')->refresh();

    //         $user = auth('api')->user();

    //         return $this->respondWithToken($token, $user);
    //     } catch (TokenExpiredException $e) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Refresh token expired. Please login again.',
    //         ], 401);
    //     } catch (JWTException $e) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Token invalid or not provided',
    //         ], 401);
    //     }
    // }

    protected function respondWithToken($token, $user)
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

            // ================= EXISTING USER =================
            if ($user) {

                if ($user->is_verified) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This email is already registered.',
                    ], 409);
                }

                $user->update([
                    'password' => Hash::make($request->password),
                    'otp' => $otp,
                    'otp_expires_at' => now()->addMinutes(3),
                    'is_verified' => false,
                    'terms_accepted_at' => now(),
                ]);

                if ($role && ! $user->hasRole('user')) {
                    $user->assignRole($role);
                }

                Mail::to($user->email)->queue(new RegisterOtpMail($otp));

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'OTP resent to your email.',
                ], 200);
            }

            // ================= NEW USER =================
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3), // consistent
                'is_verified' => false,
                'terms_accepted_at' => now(),
            ]);

            if ($role) {
                $user->assignRole($role);
            }

            Mail::to($user->email)->queue(new RegisterOtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registration successful. OTP sent to your email.',
            ], 201);
        } catch (\Exception $e) {
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
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            $token = auth('api')->login($user);

            return response()->json([
                'status' => true,
                'is_onboarding' => $user->profile()->exists(),
                'message' => 'Email already verified.',
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        }

        if (! $user->otp || (string) $user->otp !== (string) $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'can_resend_otp' => true,
            ], 400);
        }

        $user->forceFill([
            'is_verified' => true,
            'otp' => null,
            'otp_expires_at' => null,
        ])->save();

        $token = auth('api')->login($user);

        // -----------------------------------------
        // Trigger the Login event to log the successful login activity
        $payload = auth('api')->setToken($token)->getPayload(); // Get the payload of the token
        $tokenId = $payload->get('jti'); // Get the token ID (jti) from the payload
        request()->merge(['current_token_id' => $tokenId]); // Merge the token ID into the request for later use
        event(new Login('api', $user, false)); // Trigger the Login event to log the successful login activity
        // -----------------------------------------

        return response()->json([
            'status' => true,
            'is_onboarding' => $user->profile()->exists(),
            'message' => 'Email verified successfully.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
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

        if ($user->otp_expires_at && now()->lt($user->otp_expires_at)) {

            $remainingSeconds = (int) ceil(now()->diffInSeconds($user->otp_expires_at, false));

            return response()->json([
                'status' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
            ], 429);
        }

        try {
            $otp = rand(1000, 9999);

            $user->forceFill([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3),
                'is_verified' => false,
            ])->save();

            Mail::to($user->email)->queue(new RegisterOtpMail($otp));

            return response()->json([
                'status' => true,
                'message' => 'OTP resent to your email.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to resend OTP.',
            ], 500);
        }
    }
}
