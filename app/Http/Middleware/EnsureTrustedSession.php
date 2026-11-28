<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;

class EnsureTrustedSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $tokenId = null;
        if (auth('api')->check() && auth('api')->parser()->hasToken()) {
            $tokenId = auth('api')->getPayload()->get('jti');
        } elseif (! app()->environment('testing')) {
            $tokenId = session()->getId();
        }

        if ($tokenId) {
            $currentActivity = LoginActivity::where('user_id', $user->id)
                ->where('token_id', $tokenId)
                ->first();

            if (! $currentActivity || ! $currentActivity->is_trusted) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'requires_trusted_session' => true,
                    'message' => 'This session is not verified yet. Please confirm this login from your security alert email or use a trusted device.',
                ], 403);
            }
        }

        return $next($request);
    }
}
