<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Notifications\AccountDeletionRequestedNotification;
use App\Services\Auth\DeviceTrustService;
use App\Services\Auth\RefreshTokenService;
use App\Services\Auth\SessionIssuanceService;
use App\Services\Security\SecurityAuditService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

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

        $trustedCombinations = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Successful')
            ->where('is_trusted', true)
            ->select('device', 'location')
            ->distinct()
            ->get()
            ->map(fn ($item): string => $item->device.'|'.$item->location)
            ->toArray();

        foreach ($unresolvedLogins as $login) {
            $currentCombo = $login->device.'|'.$login->location;
            $seenBefore = in_array($currentCombo, $trustedCombinations);

            if (! $seenBefore) {
                $isSuspicious = true;
                $suspiciousId = $login->id;

                if ($login->token_id === $currentTokenId) {
                    $suspiciousStatus = 'New login detected';
                    $canResolveFromHere = false;
                } else {
                    $suspiciousStatus = 'Suspicious activity detected';
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

        $score = 10;
        if ($user->has_password) {
            $score += 10;
        }
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
            $recoveryCodes = [];
            if ($user->two_factor_recovery_codes) {
                try {
                    $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
                } catch (\Throwable) {
                    $recoveryCodes = [];
                }
            }

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

        $currentActivity = $currentTokenId
            ? LoginActivity::where('user_id', $user->id)->where('token_id', $currentTokenId)->first()
            : null;
        $isCurrentSessionTrusted = $currentActivity ? (bool) $currentActivity->is_trusted : (! $currentTokenId);

        return response()->json([
            'success' => true,
            'data' => [
                'security_score' => [
                    'percentage' => $score,
                    'rating' => $securityRating,
                    'last_checked' => now()->toISOString(),
                ],
                'has_password' => (bool) $user->has_password,
                'password_strength' => $user->has_password ? 'Strong' : 'Not set',
                'two_factor_auth' => $is2faEnabled ? 'Enabled' : 'Disabled',
                // 'active_sessions' => LoginActivity::where('user_id', $user->id)->where('is_active', true)->count() . ' active devices',
                'active_sessions' => $activeCount.' active devices',
                'account_recovery' => $isRecoveryVerified ? 'Email verified' : 'Not verified',
                'login_activity' => $suspiciousStatus,
                // 'suspicious_id' => $suspiciousId,
                'is_suspicious' => $isSuspicious,
                'can_resolve_from_here' => $canResolveFromHere && $isCurrentSessionTrusted,
                'is_current_session_trusted' => $isCurrentSessionTrusted,
                'two_factor_enabled' => (bool) $user->two_factor_confirmed_at,
                'backup_codes_count' => $backupCodesCountText,
                'recovery_email' => $user->recovery_email,
                'recovery_email_verified' => (bool) $user->recovery_email_verified_at,
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
            ->map(fn ($item): string => $item->device.'|'.$item->location)
            ->toArray();

        $suspiciousList = [];

        foreach ($unresolvedLogins as $login) {
            $currentCombo = $login->device.'|'.$login->location;

            if (! in_array($currentCombo, $trustedCombinations)) {

                $isMobile = ($login->browser === 'Native Mobile App');

                $suspiciousList[] = [
                    'id' => $login->id,
                    'device' => $login->device,
                    'browser' => $login->browser,
                    'location' => $login->location,
                    'ip_address' => $login->ip_address,
                    'is_mobile' => $isMobile,
                    'login_at' => $login->login_at ? Carbon::parse($login->login_at)->toISOString() : null,
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

        $items = $sessions->map(function ($activity) use ($currentTokenId): array {
            $isCurrent = $activity->token_id === $currentTokenId;

            $isMobile = ($activity->browser === 'Native Mobile App');

            return [
                'id' => $activity->id,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'ip_address' => $activity->ip_address,
                'location' => $activity->location,
                'is_active' => (bool) $activity->is_active,
                'is_current' => $isCurrent,
                'is_mobile' => $isMobile,
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

        $user = auth()->user();
        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $trustedCombinations = LoginActivity::where('user_id', $user->id)
            ->where('is_trusted', true)
            ->select('device', 'location')
            ->distinct()
            ->get()
            ->map(fn ($item): string => $item->device.'|'.$item->location)
            ->toArray();

        $paginator = LoginActivity::where('user_id', $user->id)
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

        $items = collect($paginator->items())->map(function ($activity) use ($currentTokenId, $trustedCombinations): array {
            $isCurrent = $activity->token_id === $currentTokenId;
            $isActive = (bool) $activity->is_active;
            $isTrusted = (bool) $activity->is_trusted;
            $isResolved = (bool) $activity->is_resolved;

            $currentCombo = $activity->device.'|'.$activity->location;

            $isSuspicious = (
                $activity->status === 'Successful' &&
                ! $isResolved &&
                ! in_array($currentCombo, $trustedCombinations)
            );

            $isBlocked = (
                $activity->status === 'Blocked' ||
                ($activity->status === 'Successful' && ! $isActive && $isResolved && ! $isTrusted)
            );

            if ($isTrusted) {
                $activityStatus = 'Trusted Device';
            } elseif ($isSuspicious) {
                $activityStatus = 'Suspicious';
            } elseif ($isBlocked) {
                $activityStatus = 'Blocked';
            } else {
                $activityStatus = 'Regular Device';
            }

            // ৪. signin_status (Current Device/Signed in/Logged out)
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
                'is_mobile' => ($activity->browser === 'Native Mobile App'),
                'signin_status' => $signinStatus,
                'activity_status' => $activityStatus,
                'is_suspicious' => $isSuspicious,
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

    public function getLoginActivityDetails($id): JsonResponse
    {
        $user = auth()->user();

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $activity = LoginActivity::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity details not found.',
            ], 404);
        }

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

        $isMobile = ($activity->browser === 'Native Mobile App');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $activity->id,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'ip_address' => $activity->ip_address,
                'location' => $activity->location,
                'status' => $activity->status,
                'is_active' => $isActive,
                'is_current' => $isCurrent,
                'is_mobile' => $isMobile,
                // 'is_resolved' => (bool)$activity->is_resolved,
                // 'is_trusted' => (bool)$activity->is_trusted,
                'signin_status' => $signinStatus,
                'login_at' => $activity->login_at ? Carbon::parse($activity->login_at)->toISOString() : null,
                'created_at' => $activity->created_at->toISOString(),
            ],
        ]);
    }

    public function revokeSession($id): JsonResponse
    {
        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $session = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found or already revoked.'], 404);
        }

        if ($session->token_id === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot revoke your current active session.',
            ], 403);
        }

        $session->update(['is_active' => false]);
        app(RefreshTokenService::class)->revokeForActivities([$session->id]);
        $this->blacklistTokenIds([$session->token_id]);
        app(SecurityAuditService::class)->log(auth()->id(), 'session_revoked', [
            'activity_id' => $session->id,
            'device' => $session->device,
        ]);

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

        $otherSessions = LoginActivity::where('user_id', auth()->id())
            ->where(function ($query) use ($currentTokenId): void {
                $query->where('token_id', '!=', $currentTokenId)
                    ->orWhereNull('token_id');
            })
            ->where('is_active', true);

        $tokenIds = (clone $otherSessions)->pluck('token_id')->all();
        $activityIds = (clone $otherSessions)->pluck('id')->all();
        $otherSessions->update(['is_active' => false]);
        app(RefreshTokenService::class)->revokeForActivities($activityIds);
        $this->blacklistTokenIds($tokenIds);
        app(SecurityAuditService::class)->log(auth()->id(), 'sessions_signed_out_all', [
            'count' => count($activityIds),
        ], 'warning');

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

        app(RefreshTokenService::class)->revokeForActivities([$activity->id]);

        if ($activity->is_active && $activity->token_id) {
            $this->blacklistTokenIds([$activity->token_id]);
        }

        $activity->delete();
        app(SecurityAuditService::class)->log($user, 'login_activity_deleted', ['activity_id' => (int) $id]);

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

        $otherActivities = LoginActivity::where('user_id', $user->id)
            ->where(function ($query) use ($currentTokenId): void {
                $query->where('token_id', '!=', $currentTokenId)
                    ->orWhereNull('token_id');
            });

        $activeTokens = (clone $otherActivities)
            ->where('is_active', true)
            ->pluck('token_id')
            ->all();
        $activityIds = (clone $otherActivities)->pluck('id')->all();
        app(RefreshTokenService::class)->revokeForActivities($activityIds);
        $this->blacklistTokenIds($activeTokens);

        $deletedCount = $otherActivities->delete();
        app(SecurityAuditService::class)->log($user, 'login_activities_cleared', ['deleted_count' => $deletedCount], 'warning');

        return response()->json([
            'success' => true,
            'message' => 'All other login activities for your account have been cleared.',
        ]);
    }

    public function bulkDeleteLoginActivities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $user = auth()->user();

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $targetQuery = LoginActivity::where('user_id', $user->id)
            ->whereIn('id', $validated['ids'])
            ->where(function ($query) use ($currentTokenId): void {
                $query->where('token_id', '!=', $currentTokenId)
                    ->orWhereNull('token_id');
            });

        $activeTokens = (clone $targetQuery)
            ->where('is_active', true)
            ->pluck('token_id')
            ->all();
        $activityIds = (clone $targetQuery)->pluck('id')->all();
        app(RefreshTokenService::class)->revokeForActivities($activityIds);
        $this->blacklistTokenIds($activeTokens);

        $deletedCount = $targetQuery->delete();
        app(SecurityAuditService::class)->log($user, 'login_activities_bulk_deleted', ['deleted_count' => $deletedCount]);

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} login activity record(s) deleted successfully.",
            'deleted_count' => $deletedCount,
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
            app(SecurityAuditService::class)->log(auth()->id(), 'device_trusted', [
                'activity_id' => $activity->id,
                'device' => $activity->device,
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
        $updated = LoginActivity::where('user_id', auth()->id())
            ->where('status', 'Successful')
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'is_trusted' => true,
            ]);

        app(SecurityAuditService::class)->log(auth()->id(), 'devices_trusted_all', ['count' => $updated]);

        return response()->json(['success' => true, 'message' => 'All activities marked as trusted.']);
    }

    /**
     * if user says "No, it wasn't me"
     */
    public function secureAccount($id): JsonResponse
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
                'message' => 'You cannot secure or block your current active session.',
            ], 403);
        }

        $activity->update([
            'is_active' => false,
            'is_resolved' => true,
            'is_trusted' => false,
        ]);

        if ($activity->device_token_hash) {
            LoginActivity::where('user_id', $user->id)
                ->where('device_token_hash', $activity->device_token_hash)
                ->update(['is_trusted' => false]);
        }

        app(RefreshTokenService::class)->revokeForActivities([$activity->id]);
        $this->blacklistTokenIds([$activity->token_id]);
        app(SecurityAuditService::class)->log($user, 'suspicious_session_blocked', [
            'activity_id' => $activity->id,
            'device' => $activity->device,
            'ip' => $activity->ip_address,
        ], 'critical');

        return response()->json([
            'success' => true,
            'message' => 'The suspicious session has been blocked successfully. We recommend changing your password as soon as possible.',
            'action_required' => 'change_password',
        ]);
    }

    public function resolveFromEmail(Request $request, $id): Factory|View|null
    {
        $activity = LoginActivity::findOrFail($id);

        // Single-use check: If the activity is already resolved, show an expired message
        if ($activity->is_resolved) {
            return view('security.resolution', ['type' => 'expired']);
        }

        $action = $request->query('action');

        if ($action === 'trust') {
            $activity->update(['is_resolved' => true, 'is_trusted' => true]);
            app(SecurityAuditService::class)->log($activity->user_id, 'device_trusted_via_email', [
                'activity_id' => $activity->id,
                'device' => $activity->device,
            ]);

            return view('security.resolution', ['type' => 'trust']);
        }

        if ($action === 'block') {
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

        return null;
    }

    // This method handles the password update after the user confirms securing their account from the email alert.
    public function updatePasswordFromAlert(Request $request): Factory|View
    {
        $activityId = (int) $request->query('activity_id');
        $signedUserId = (int) $request->query('user_id');
        $activity = LoginActivity::findOrFail($activityId);

        abort_if((int) $activity->user_id !== $signedUserId, 403, 'Invalid security resolution request.');

        if ($activity->is_resolved) {
            return view('security.resolution', ['type' => 'expired']);
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ]);

        $user = User::findOrFail($signedUserId);
        $user->update([
            'password' => Hash::make($request->password),
            'has_password' => true,
        ]);

        $tokenIds = LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('token_id')
            ->all();
        LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        app(RefreshTokenService::class)->revokeAllForUser($user->id);
        $this->blacklistTokenIds($tokenIds);

        $activity->update([
            'is_active' => false,
            'is_resolved' => true,
            'is_trusted' => false,
        ]);

        if ($activity->device_token_hash) {
            LoginActivity::where('user_id', $user->id)
                ->where('device_token_hash', $activity->device_token_hash)
                ->update(['is_trusted' => false]);
        }

        app(SecurityAuditService::class)->log($user, 'password_reset_via_alert', [
            'activity_id' => $activity->id,
            'revoked_sessions' => count($tokenIds),
        ], 'critical');

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
        $user = auth('api')->user();

        $rules = [
            'reason' => 'required|string|max:1000',
        ];
        if ($user->has_password) {
            $rules['password'] = 'required';
        }
        $request->validate($rules);

        if ($user->has_password && ! Hash::check((string) $request->password, (string) $user->password)) {
            return response()->json(['errors' => ['password' => ['The provided password is incorrect.']]], 422);
        }

        $permanentDeleteAt = now()->addDays(30);

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => $user->first_name.' '.$user->last_name,
            'user_email' => $user->email,
            'reason' => $request->reason,
            'requested_at' => now(),
            'permanent_delete_at' => $permanentDeleteAt,
        ]);

        $user->notify(new AccountDeletionRequestedNotification($user, $permanentDeleteAt));

        try {
            $activeQuery = LoginActivity::where('user_id', $user->id)->where('is_active', true);
            $tokenIds = (clone $activeQuery)->pluck('token_id')->all();
            $activeQuery->update(['is_active' => false]);
            $this->blacklistTokenIds($tokenIds);
            app(RefreshTokenService::class)->revokeAllForUser($user->id);
        } catch (\Exception) {
            //
        }

        app(SecurityAuditService::class)->log($user, 'account_delete_requested', [
            'permanent_delete_at' => $permanentDeleteAt->toISOString(),
        ], 'critical');

        // remove 2FA and recovery codes before soft deleting the user
        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $user->delete();
        auth('api')->logout();

        return response()->json([
            'message' => 'Your account deletion request has been received. You have 30 days to reactivate it before permanent deletion.',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if (! $request->filled('password') && ! $request->filled('otp')) {
            return response()->json([
                'success' => false,
                'message' => 'Credentials or recovery verification code is required to restore your account.',
            ], 422);
        }

        try {
            $user = User::withTrashed()->where('email', $request->email)->first();

            if (! $user || ! $user->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending account deletion found for this email.',
                ], 404);
            }

            if ($user->is_suspended) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }

            $deletionLog = DeletedAccountLog::where('user_id', $user->id)->latest()->first();
            if ($deletionLog && now()->gt($deletionLog->permanent_delete_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The restoration period for this account has expired.',
                ], 410);
            }

            if ($request->filled('password')) {
                if (! Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The provided password is incorrect. Restoration failed.',
                    ], 401);
                }
            } elseif ($request->filled('otp')) {
                $otpRecord = Otp::where('user_id', $user->id)
                    ->where('type', OtpType::RECOVERY->value)
                    ->first();

                if (! $otpRecord || (string) $otpRecord->otp !== (string) $request->otp) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid recovery code.',
                    ], 401);
                }

                if (! $otpRecord->expires_at || now()->gt($otpRecord->expires_at)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Recovery code has expired.',
                    ], 401);
                }

                $otpRecord->delete();
            }

            return DB::transaction(function () use ($user) {
                $user->restore();

                DeletedAccountLog::where('user_id', $user->id)->delete();

                $session = app(SessionIssuanceService::class)->issueSession($user);
                app(SecurityAuditService::class)->log($user, 'account_restored', [], 'warning');

                $response = response()->json([
                    'success' => true,
                    'message' => 'Welcome back! Your account has been successfully reactivated.',
                    'data' => [
                        'is_onboarding' => $session['is_onboarding'],
                        'user' => $session['user'],
                        'token' => $session['token'],
                        'token_type' => 'bearer',
                        'expires_in' => auth('api')->factory()->getTTL() * 60,
                    ],
                ]);

                app(RefreshTokenService::class)->attachRefreshCookie($response, $session['refresh_token'], request());
                app(DeviceTrustService::class)->attachDeviceCookie($response, $session['device_id'], request());

                return $response;
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while restoring the account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSecurityAuditLogs(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $logs = SecurityAuditLog::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $formatted = collect($logs->items())->map(function (SecurityAuditLog $log): array {
            return [
                'id' => $log->id,
                'event' => $log->event,
                'severity' => $log->severity,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
            ],
        ]);
    }

    /**
     * Blacklist JWT token IDs in cache so deleted/revoked sessions cannot be re-activated.
     *
     * @param  array<int, string|null>  $tokenIds
     */
    private function blacklistTokenIds(array $tokenIds): void
    {
        $ttl = now()->addMinutes((int) config('jwt.rolling_window', 43200));

        foreach ($tokenIds as $tokenId) {
            if (is_string($tokenId) && $tokenId !== '') {
                Cache::put("revoked_jti:{$tokenId}", true, $ttl);
            }
        }
    }
}
