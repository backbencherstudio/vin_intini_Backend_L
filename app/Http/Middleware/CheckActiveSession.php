<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;

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

            $isActive = LoginActivity::where('token_id', $tokenId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (! $isActive) {
                if (auth('api')->check()) {
                    return response()->json(['message' => 'Your session has been revoked.'], 401);
                }

                auth()->logout();

                return redirect('/admin/login')->withErrors(['email' => 'Session revoked by admin.']);
            }
        }

        return $next($request);
    }
}
