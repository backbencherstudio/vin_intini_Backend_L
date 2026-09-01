<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use App\Models\DeletedAccountLog;
use App\Mail\AccountDeletionRequestedMail;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class SecuritySettingsController extends Controller
{
    public function getSecurityOverview(): JsonResponse
    {
        $user = auth()->user();
        $lookBackDays = now()->subDays(15);
        $currentTokenId = auth('api')->check() ? auth('api')->payload()->get('jti') : session()->getId();

        $is2faEnabled = ! is_null($user->two_factor_confirmed_at);
        $isRecoveryVerified = ! is_null($user->recovery_email_verified_at);

        $unresolvedLogins = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Successful')
            ->where('is_resolved', false)
            ->where('created_at', '>=', $lookBackDays)
            ->get();

        $isSuspicious = false;
        $suspiciousStatus = 'No suspicious activity';
        $suspiciousId = null;
        $canResolveFromHere = true;

        foreach ($unresolvedLogins as $login) {
            $seenBefore = LoginActivity::where('user_id', $user->id)
                ->where('status', 'Successful')
                ->where('location', $login->location)
                ->where('device', $login->device)
                ->where('is_trusted', true)
                ->exists();

            if (! $seenBefore) {
                $isSuspicious = true;
                $suspiciousId = $login->id;

                if ($login->token_id === $currentTokenId) {
                    $suspiciousStatus = 'New login detected. Please verify this session via email.';
                    $canResolveFromHere = false;
                } else {
                    $suspiciousStatus = 'New login from unrecognized location/device. Was this you?';
                    $canResolveFromHere = true;
                }
                break;
            }
        }

        $failedCount = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Failed')
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if (! $isSuspicious && $failedCount >= 5) {
            $isSuspicious = true;
            $suspiciousStatus = "Multiple failed login attempts detected ($failedCount)";
            $canResolveFromHere = true;
        }

        $score = 20;
        if ($is2faEnabled) {
            $score += 40;
        }
        if ($isRecoveryVerified) {
            $score += 20;
        }
        if ($user->profile()->exists()) {
            $score += 10;
        }
        if (! $isSuspicious) {
            $score += 10;
        }

        $score = max(0, min($score, 100));
        $securityRating = ($score > 80) ? 'Strong' : (($score > 50) ? 'Medium' : 'Weak');

        // --- backup codes count ---
        if (! $user->two_factor_confirmed_at) {
            $backupCodesCountText = 'Enable 2FA to generate backup codes';
        } else {
            $recoveryCodes = $user->two_factor_recovery_codes
                ? json_decode(decrypt($user->two_factor_recovery_codes), true)
                : [];

            $remaining = count($recoveryCodes);
            $used = 10 - $remaining;
            $backupCodesCountText = "{$used} of 10 codes used";
        }
        // ------------------------------------

        $rollingMinutes = config('jwt.rolling_window', 43200);
        $activeCount = LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('updated_at', '>=', now()->subMinutes($rollingMinutes))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'security_score' => [
                    'percentage' => $score,
                    'rating' => $securityRating,
                    'last_checked' => now()->toISOString(),
                ],
                'password_strength' => 'Strong',
                'two_factor_auth' => $is2faEnabled ? 'Enabled' : 'Disabled',
                // 'active_sessions' => LoginActivity::where('user_id', $user->id)->where('is_active', true)->count() . ' active devices',
                'active_sessions' => $activeCount . ' active devices',
                'account_recovery' => $isRecoveryVerified ? 'Email verified' : 'Not verified',
                'login_activity' => $suspiciousStatus,
                // 'suspicious_id' => $suspiciousId,
                'is_suspicious' => $isSuspicious,
                'can_resolve_from_here' => $canResolveFromHere,
                'two_factor_enabled' => $user->two_factor_confirmed_at ? true : false,
                'backup_codes_count' => $backupCodesCountText,
                'recovery_email' => $user->recovery_email,
                'recovery_email_verified' => $user->recovery_email_verified_at ? true : false,
            ],
        ]);
    }

    public function getSuspiciousActivitiesList(): JsonResponse
    {
        $user = auth()->user();
        $lookBackDays = now()->subDays(15);

        $unresolvedLogins = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Successful')
            ->where('is_resolved', false)
            ->where('created_at', '>=', $lookBackDays)
            ->orderBy('login_at', 'desc')
            ->get();

        $trustedCombinations = LoginActivity::where('user_id', $user->id)
            ->where('is_trusted', true)
            ->select('device', 'location')
            ->distinct()
            ->get()
            ->map(fn($item) => $item->device . '|' . $item->location)
            ->toArray();

        $suspiciousList = [];

        foreach ($unresolvedLogins as $login) {
            $currentCombo = $login->device . '|' . $login->location;

            if (!in_array($currentCombo, $trustedCombinations)) {
                $suspiciousList[] = [
                    'id' => $login->id,
                    'device' => $login->device,
                    'browser' => $login->browser,
                    'location' => $login->location,
                    'ip_address' => $login->ip_address,
                    'login_at' => Carbon::parse($login->login_at)->toISOString(),
                    'warning_message' => "Unrecognized login from {$login->location}",
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $suspiciousList,
        ]);
    }

    public function getActiveSessions(): JsonResponse
    {
        $user = auth()->user();
        $rollingMinutes = config('jwt.rolling_window', 43200);

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $sessions = LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('updated_at', '>=', now()->subMinutes($rollingMinutes))
            ->orderByRaw('token_id = ? DESC', [$currentTokenId])
            ->orderBy('updated_at', 'desc')
            ->get();

        $items = $sessions->map(function ($activity) use ($currentTokenId) {
            $isCurrent = $activity->token_id === $currentTokenId;

            return [
                'id' => $activity->id,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'ip_address' => $activity->ip_address,
                'location' => $activity->location,
                'is_active' => (bool) $activity->is_active,
                'is_current' => $isCurrent,
                'status' => $isCurrent ? 'Current device' : 'Signed in',
                'login_at' => $activity->login_at ? Carbon::parse($activity->login_at)->toISOString() : null,
                // 'last_activity' => $activity->updated_at ? Carbon::parse($activity->updated_at)->toISOString() : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function getLoginActivities(Request $request): JsonResponse
    {
        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $paginator = LoginActivity::where('user_id', auth()->id())
            ->orderByRaw("
            CASE
                WHEN token_id = ? THEN 1
                WHEN is_active = 1 AND status = 'Successful' THEN 2
                WHEN is_active = 0 AND status = 'Successful' THEN 3
                WHEN status = 'Failed' THEN 4
                ELSE 5
            END ASC
        ", [$currentTokenId])
            ->orderBy('login_at', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($activity) use ($currentTokenId) {
            $isCurrent = $activity->token_id === $currentTokenId;
            $isActive = (bool) $activity->is_active;

            if ($activity->status === 'Failed') {
                $signinStatus = 'Failed attempt';
            } elseif ($isCurrent) {
                $signinStatus = 'Current device';
            } elseif ($isActive) {
                $signinStatus = 'Signed in';
            } else {
                $signinStatus = 'Logged out';
            }

            return [
                'id' => $activity->id,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'ip_address' => $activity->ip_address,
                'location' => $activity->location,
                'status' => $activity->status,
                'is_active' => $isActive,
                'is_current' => $isCurrent,
                'signin_status' => $signinStatus,
                'login_at' => $activity->login_at ? Carbon::parse($activity->login_at)->toISOString() : null,
                'created_at' => $activity->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => ['items' => $items],
            'total' => $paginator->total(),
            'limit' => $perPage,
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function revokeSession($id): JsonResponse
    {
        $session = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found or already revoked.'], 404);
        }

        $session->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device has been signed out successfully.',
        ]);
    }

    public function signOutAllSessions(): JsonResponse
    {
        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        LoginActivity::where('user_id', auth()->id())
            ->where('token_id', '!=', $currentTokenId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'All other sessions have been signed out successfully.',
        ]);
    }

    public function deleteLoginActivity($id): JsonResponse
    {
        $user = auth()->user();

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $activity = LoginActivity::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $activity) {
            return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
        }

        if ($activity->token_id === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your current active session.',
            ], 403);
        }

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login activity deleted successfully.',
        ]);
    }

    public function deleteAllLoginActivities(): JsonResponse
    {
        $user = auth()->user();

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        LoginActivity::where('user_id', $user->id)
            ->where(function ($query) use ($currentTokenId) {
                $query->where('token_id', '!=', $currentTokenId)
                    ->orWhereNull('token_id');
            })
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All other login activities for your account have been cleared.',
        ]);
    }

    /**
     * suspicious login resolution (Yes, it was me)
     */
    public function resolveSuspiciousLogin($id): JsonResponse
    {
        $activity = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($activity) {
            $activity->update([
                'is_resolved' => true,
                'is_trusted' => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Device marked as trusted.']);
        }

        return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
    }

    /**
     * all unresolved activities resolved (Yes, it was me for all)
     */
    public function resolveAllActivities(): JsonResponse
    {
        LoginActivity::where('user_id', auth()->id())
            ->where('status', 'Successful')
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'is_trusted' => true,
            ]);

        return response()->json(['success' => true, 'message' => 'All activities marked as trusted.']);
    }

    /**
     * if user says "No, it wasn't me"
     */
    public function secureAccount($id): JsonResponse
    {
        $user = auth()->user();

        $activity = LoginActivity::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($activity) {
            $activity->update([
                'is_active' => false,
                'is_resolved' => true,
                'is_trusted' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'The suspicious session has been blocked successfully. We recommend changing your password as soon as possible.',
                'action_required' => 'change_password',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
    }

    public function resolveFromEmail(Request $request, $id)
    {
        $activity = LoginActivity::findOrFail($id);

        // Single-use check: If the activity is already resolved, show an expired message
        if ($activity->is_resolved) {
            return view('security.resolution', ['type' => 'expired']);
        }

        $action = $request->query('action');

        if ($action === 'trust') {
            $activity->update(['is_resolved' => true, 'is_trusted' => true]);

            return view('security.resolution', ['type' => 'trust']);
        }

        if ($action === 'block') {
            $activity->update(['is_active' => false]);
            LoginActivity::where('user_id', $activity->user_id)->update(['is_active' => false]);

            $postUrl = URL::temporarySignedRoute(
                'security.update-password',
                now()->addMinutes(20),
                ['user_id' => $activity->user_id, 'activity_id' => $activity->id]
            );

            return view('security.resolution', [
                'type' => 'block',
                'postUrl' => $postUrl,
                'user_id' => $activity->user_id,
            ]);
        }
    }

    // This method handles the password update after the user clicks "No, it wasn't me" in the email alert.
    public function updatePasswordFromAlert(Request $request)
    {
        $activityId = $request->query('activity_id');
        $activity = LoginActivity::findOrFail($activityId);

        if ($activity->is_resolved) {
            return view('security.resolution', ['type' => 'expired']);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ]);

        $user = User::find($request->user_id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $activity->update(['is_resolved' => true, 'is_trusted' => false]);

        return view('security.resolution', ['type' => 'success_reset']);
    }

    public function updatePrivacySettings(Request $request)
    {
        $request->validate([
            'privacy_profile_activity' => 'nullable|string|in:everyone,nobody,only_connected',
            'privacy_profile_visibility' => 'nullable|string|in:everyone,nobody,only_connected',
        ]);

        $profile = auth()->user()->profile;

        $profile->update($request->only([
            'privacy_profile_activity',
            'privacy_profile_visibility',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Privacy settings updated successfully',
            'data' => $profile,
        ]);
    }

    public function requestDelete(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'password' => 'required'
        ]);

        $user = auth('api')->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['password' => ['The provided password is incorrect.']]], 422);
        }

        $permanentDeleteAt = now()->addDays(30);

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'user_email' => $user->email,
            'reason' => $request->reason,
            'requested_at' => now(),
            'permanent_delete_at' => $permanentDeleteAt,
        ]);

        Mail::to($user->email)->queue(new AccountDeletionRequestedMail($user, $permanentDeleteAt));

        try {
            $payload = auth('api')->payload();
            if ($payload) {
                $tokenId = $payload->get('jti');
                LoginActivity::where('token_id', $tokenId)
                    ->where('user_id', $user->id)
                    ->update(['is_active' => 0]);
            }
        } catch (\Exception $e) {
            //
        }

        //remove 2FA and recovery codes before soft deleting the user
        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $user->delete();
        auth('api')->logout();

        return response()->json([
            'message' => 'Your account deletion request has been received. You have 30 days to reactivate it before permanent deletion.'
        ]);
    }

    public function restore(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token not provided'], 401);
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub');

            $user = User::withTrashed()->find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found.'
                ], 404);
            }

            if ($user->trashed()) {
                $user->restore();

                DeletedAccountLog::where('user_id', $user->id)->delete();

                $tokenId = $payload->get('jti');
                request()->merge(['current_token_id' => $tokenId]);
                event(new \Illuminate\Auth\Events\Login('api', $user, false));

                $roleName = $user->getRoleNames()->first();
                $user->makeHidden('roles');
                $user->role = $roleName;

                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! Your account has been successfully reactivated.',
                    'data' => [
                        'is_onboarding' => $user->profile()->exists(),
                        'user' => $user,
                        'token' => $token,
                        'token_type' => 'bearer',
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Your account is already active.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
