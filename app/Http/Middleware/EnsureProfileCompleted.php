<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $hasProfile = $user->relationLoaded('profile')
            ? $user->profile !== null
            : $user->profile()->exists();

        if (!$hasProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile setup required.',
                'next' => [
                    'method' => 'POST',
                    'path' => '/api/setup-profile',
                ],
            ], 403);
        }

        return $next($request);
    }
}
