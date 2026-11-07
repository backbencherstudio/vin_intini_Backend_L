<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChallengeService
{
    /**
     * Create a purpose-bound challenge token for a user.
     */
    public function createChallenge(User $user, string $purpose = '2fa', int $ttlMinutes = 5): string
    {
        $challengeToken = Str::random(40);
        Cache::put("challenge:{$purpose}:{$challengeToken}", $user->id, now()->addMinutes($ttlMinutes));

        return $challengeToken;
    }

    /**
     * Verify and consume a purpose-bound challenge token.
     */
    public function verifyAndConsumeChallenge(string $token, User $user, string $purpose = '2fa'): bool
    {
        $cacheKey = "challenge:{$purpose}:{$token}";
        $legacyKey = "2fa_challenge:{$token}";

        $challengeUserId = Cache::pull($cacheKey);
        if (! $challengeUserId && $purpose === '2fa') {
            $challengeUserId = Cache::pull($legacyKey);
        }

        if (! $challengeUserId) {
            return false;
        }

        return (int) $challengeUserId === (int) $user->id;
    }
}
