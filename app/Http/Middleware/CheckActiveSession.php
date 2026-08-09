<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LoginActivity;

class CheckActiveSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user) {
            $tokenId = null;

            if (auth('api')->check()) {
                $tokenId = auth('api')->payload()->get('jti'); // API
            } else {
                $tokenId = session()->getId(); // Web/Admin
            }

            $isActive = LoginActivity::where('token_id', $tokenId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (!$isActive) {
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
