<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityAuditService
{
    /**
     * Record an immutable security audit event without ever breaking the caller flow.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        User|int|null $user,
        string $event,
        array $metadata = [],
        string $severity = 'info',
        ?Request $request = null,
    ): void {
        try {
            $req = $request ?? (app()->bound('request') ? request() : null);
            $userId = $user instanceof User ? $user->id : $user;

            $tokenId = null;
            try {
                if (auth('api')->check() && auth('api')->parser()->hasToken()) {
                    $tokenId = auth('api')->payload()->get('jti');
                }
            } catch (\Throwable) {
                $tokenId = null;
            }

            $userAgent = $req?->userAgent();

            SecurityAuditLog::create([
                'user_id' => $userId,
                'event' => $event,
                'severity' => in_array($severity, ['info', 'warning', 'critical'], true) ? $severity : 'info',
                'ip_address' => $req?->ip(),
                'user_agent' => is_string($userAgent) ? mb_substr($userAgent, 0, 512) : null,
                'token_id' => is_string($tokenId) ? $tokenId : null,
                'metadata' => empty($metadata) ? null : $metadata,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Audit persistence must never break authentication or revocation flows.
        }
    }
}
