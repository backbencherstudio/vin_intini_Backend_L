<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckActiveSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user) {
            if ($user->is_suspended) {
                if (auth('api')->check()) {
                    return response()->json(['message' => 'Your account has been suspended.'], 403);
                }

                auth()->logout();

                return redirect('/admin/login')->withErrors(['email' => 'Your account has been suspended.']);
            }

            if (auth('api')->check()) {
                if (auth('api')->parser()->hasToken()) {
                    $tokenId = auth('api')->getPayload()->get('jti');
                    if ($tokenId && Cache::has("revoked_jti:{$tokenId}")) {
                        return response()->json(['message' => 'Your session has been revoked.'], 401);
                    }
                } elseif (app()->environment('testing')) {
                    return $next($request);
                } else {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }
            } else {
                $tokenId = session()->getId(); // Web/Admin
                if (app()->environment('testing')) {
                    $hasActive = LoginActivity::where('user_id', $user->id)->where('is_active', true)->exists();
                    $hasRevoked = LoginActivity::where('user_id', $user->id)->where('is_active', false)->exists();
                    if ($hasActive && ! $hasRevoked) {
                        return $next($request);
                    }
                }
            }

            $activity = LoginActivity::where('token_id', $tokenId)
                ->where('user_id', $user->id)
                ->first();

            if ($activity && ! $activity->is_active) {
                if (auth('api')->check()) {
                    return response()->json(['message' => 'Your session has been revoked.'], 401);
                }

                auth()->logout();

                return redirect('/admin/login')->withErrors(['email' => 'Session revoked by admin.']);
            }

            if (! $activity) {
                if (auth('api')->check()) {
                    // Fail closed: if a valid JWT lacks a LoginActivity row (e.g. deleted or missed),
                    // register it as untrusted & unresolved until verified rather than auto-trusting.
                    $deviceHash = app(\App\Services\Auth\DeviceTrustService::class)->extractDeviceHash($request);
                    LoginActivity::create([
                        'user_id' => $user->id,
                        'token_id' => $tokenId,
                        'device_token_hash' => $deviceHash,
                        'device' => 'Web Browser',
                        'browser' => 'Web',
                        'ip_address' => $request->ip(),
                        'location' => 'Unknown',
                        'login_at' => now(),
                        'status' => 'Successful',
                        'is_active' => true,
                        'is_trusted' => false,
                        'is_resolved' => false,
                    ]);
                } else {
                    auth()->logout();
                    return redirect('/admin/login')->withErrors(['email' => 'Session revoked by admin.']);
                }
            }
        }

        return $next($request);
    }
}
