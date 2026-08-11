<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Carbon\Carbon;
use App\Services\ProfileImageService;
use App\Models\LoginActivity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // Authenticated user

            return response()->json([
                'success' => true,
                'data' => [
                    'user_settings' => [
                        'first_name'    => $user->first_name,
                        'last_name'     => $user->last_name,
                        'email'         => $user->email,
                        'profile_image' => $user->profile_image_url,
                        'new_order_e_notification' => $user->new_order_e_notification ?? false,
                    ],
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateSettings(Request $request, ProfileImageService $profileImageService): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'first_name' => ['string', 'max:255'],
                'last_name' => ['string', 'max:255'],
                'email' => ['email', 'max:255', 'unique:users,email,' . $user->id],
                'mobile' => ['unique:users,mobile,' . $user->id],
                'profile_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'remove_image' => ['sometimes', 'boolean'],
            ]);

            if ($request->boolean('remove_image')) {
                $profileImageService->deleteIfLocal($user->profile_image);

                $validated['profile_image'] = null;
            }

            if ($request->hasFile('profile_image')) {
                $validated['profile_image'] = $profileImageService->storeUploaded(
                    $request->file('profile_image'),
                    $user->profile_image,
                );
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile_image' => $user->profile_image_url,
                ]
            ], 200);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateNotificationSettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'new_order_e_notification' => ['sometimes', 'boolean'],
            ]);

            if (empty($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one notification setting must be provided.'
                ], 422);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }









    //Account Security and Login Activities start from here-----------------

    public function getSecurityOverview(): JsonResponse
    {
        $user = auth()->user();
        $lookBackDays = now()->subDays(7);
        $currentTokenId = auth('api')->check() ? auth('api')->payload()->get('jti') : session()->getId();

        $is2faEnabled = !is_null($user->two_factor_confirmed_at);
        $isRecoveryVerified = !is_null($user->recovery_email_verified_at);

        $unresolvedLogins = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Successful')
            ->where('is_resolved', false)
            ->where('created_at', '>=', $lookBackDays)
            ->get();

        $isSuspicious = false;
        $suspiciousStatus = "No suspicious activity";
        $suspiciousId = null;
        $canResolveFromHere = true; // ডিফল্টভাবে বাটন দেখাবে

        foreach ($unresolvedLogins as $login) {
            $seenBefore = LoginActivity::where('user_id', $user->id)
                ->where('status', 'Successful')
                ->where('location', $login->location)
                ->where('device', $login->device)
                ->where('id', '<', $login->id)
                ->exists();

            if (!$seenBefore) {
                $isSuspicious = true;
                $suspiciousId = $login->id;

                // === মূল সিকিউরিটি লজিক ===
                // যদি বর্তমান সেশনটিই সেই সন্দেহজনক সেশন হয়, তবে সে এখান থেকে নিজেকে Trust করতে পারবে না।
                // তাকে ইমেইল চেক করতে হবে অথবা অন্য ট্রাস্টেড ডিভাইস ব্যবহার করতে হবে।
                if ($login->token_id === $currentTokenId) {
                    $suspiciousStatus = "New login detected. Please verify this session via email.";
                    $canResolveFromHere = false;
                } else {
                    $suspiciousStatus = "New login from unrecognized location/device. Was this you?";
                    $canResolveFromHere = true;
                }
                break;
            }
        }

        // ফেইল্ড লগইন চেক
        $failedCount = LoginActivity::where('user_id', $user->id)
            ->where('status', 'Failed')
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if (!$isSuspicious && $failedCount >= 5) {
            $isSuspicious = true;
            $suspiciousStatus = "Multiple failed login attempts detected ($failedCount)";
            $canResolveFromHere = true;
        }

        // স্কোর ক্যালকুলেশন
        $score = 20;
        if ($is2faEnabled) $score += 40;
        if ($isRecoveryVerified) $score += 20;
        if ($user->profile()->exists()) $score += 10;
        if (!$isSuspicious) $score += 10;

        $score = max(0, min($score, 100));
        $securityRating = ($score > 80) ? "Strong" : (($score > 50) ? "Medium" : "Weak");

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
                'active_sessions' => LoginActivity::where('user_id', $user->id)->where('is_active', true)->count() . " active devices",
                'account_recovery' => $isRecoveryVerified ? 'Email verified' : 'Not verified',
                'login_activity' => $suspiciousStatus,
                'suspicious_id' => $suspiciousId,
                'is_suspicious' => $isSuspicious,
                'can_resolve_from_here' => $canResolveFromHere // ফ্রন্টএন্ড এই ভ্যালু দেখে বাটন হাইড করবে
            ]
        ]);
    }

    public function getActiveSessions(): JsonResponse
    {
        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $sessions = LoginActivity::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderByRaw("token_id = ? DESC", [$currentTokenId])
            ->orderBy('login_at', 'desc')
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
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }


    public function getLoginActivities(Request $request): JsonResponse
    {
        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));
        $sortOrder = strtolower($request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $paginator = LoginActivity::where('user_id', auth()->id())
            ->orderByRaw("token_id = ? DESC", [$currentTokenId])
            ->orderByDesc('is_active')
            ->orderBy('login_at', $sortOrder)
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($activity) use ($currentTokenId) {
            $isCurrent = $activity->token_id === $currentTokenId;
            $isActive = (bool) $activity->is_active;

            if ($isCurrent) {
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
                'login_at' => $activity->login_at ? \Carbon\Carbon::parse($activity->login_at)->toISOString() : null,
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

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found or already revoked.'], 404);
        }

        $session->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device has been signed out successfully.'
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
            'message' => 'All other sessions have been signed out successfully.'
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

        if (!$activity) {
            return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
        }

        if ($activity->token_id === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your current active session.'
            ], 403);
        }

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login activity deleted successfully.'
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
            'message' => "All other login activities for your account have been cleared."
        ]);
    }


    /**
     * 2. suspicious login resolution (Yes, it was me)
     */
    public function resolveSuspiciousLogin($id): JsonResponse
    {
        $activity = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($activity) {
            $activity->update(['is_resolved' => true]);
            return response()->json(['success' => true, 'message' => 'Trusted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
    }

    /**
     * 3. all unresolved activities resolved (Yes, it was me for all)
     */
    public function resolveAllActivities(): JsonResponse
    {
        LoginActivity::where('user_id', auth()->id())
            ->where('is_resolved', false)
            ->update(['is_resolved' => true]);

        return response()->json(['success' => true, 'message' => 'All activities marked as trusted.']);
    }

    /**
     * if user says "No, it wasn't me"
     */
    public function secureAccount($id): JsonResponse
    {
        $activity = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($activity) {
            $activity->update([
                'is_active' => false,
                'is_resolved' => true
            ]);

            $currentTokenId = auth('api')->check() ? auth('api')->payload()->get('jti') : session()->getId();
            LoginActivity::where('user_id', auth()->id())
                ->where('token_id', '!=', $currentTokenId)
                ->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'That session has been blocked. For your security, please change your password immediately.',
                'action_required' => 'change_password'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Activity not found.'], 404);
    }


    // public function resolveFromEmail(Request $request, $id)
    // {
    //     $activity = LoginActivity::findOrFail($id);
    //     $action = $request->query('action');

    //     if ($action === 'trust') {
    //         $activity->update(['is_resolved' => true]);

    //         return view('security.resolution', ['type' => 'trust']);
    //     }

    //     if ($action === 'block') {
    //         $activity->update(['is_active' => false, 'is_resolved' => false]);

    //         LoginActivity::where('user_id', $activity->user_id)
    //             ->where('token_id', '!=', $activity->token_id)
    //             ->update(['is_active' => false]);

    //         return view('security.resolution', ['type' => 'block']);
    //     }
    // }

    public function resolveFromEmail(Request $request, $id)
    {
        $activity = LoginActivity::findOrFail($id);

        // Single-use check: If the activity is already resolved, show an expired message
        if ($activity->is_resolved) {
            return view('security.resolution', ['type' => 'expired']);
        }

        $action = $request->query('action');

        if ($action === 'trust') {
            $activity->update(['is_resolved' => true]);
            return view('security.resolution', ['type' => 'trust']);
        }

        if ($action === 'block') {
            $activity->update(['is_active' => false]);
            LoginActivity::where('user_id', $activity->user_id)->update(['is_active' => false]);

            $postUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'security.update-password',
                now()->addMinutes(20),
                ['user_id' => $activity->user_id, 'activity_id' => $activity->id]
            );

            return view('security.resolution', [
                'type' => 'block',
                'postUrl' => $postUrl,
                'user_id' => $activity->user_id
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
                Password::min(8)->mixedCase()->numbers()
            ],
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        $activity->update(['is_resolved' => true]);

        return view('security.resolution', ['type' => 'success_reset']);
    }
}
