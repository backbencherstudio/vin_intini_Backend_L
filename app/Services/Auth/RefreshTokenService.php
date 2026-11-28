<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\LoginActivity;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenService
{
    public const REFRESH_COOKIE_NAME = 'mu_refresh_token';

    public function __construct(
        protected SecurityAuditService $auditService,
    ) {}

    public function issue(
        User $user,
        ?LoginActivity $activity,
        ?string $jti,
        ?Request $request = null,
        ?string $familyId = null,
    ): array {
        $req = $request ?? request();
        $plain = bin2hex(random_bytes(32));
        $ttlMinutes = max(60, (int) (config('jwt.refresh_ttl') ?? 43200));
        $userAgent = $req->userAgent();

        $record = RefreshToken::create([
            'user_id' => $user->id,
            'login_activity_id' => $activity?->id,
            'family_id' => $familyId ?? (string) Str::uuid(),
            'token_hash' => hash('sha256', $plain),
            'jti' => $jti,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'ip_address' => $req->ip(),
            'user_agent' => is_string($userAgent) ? mb_substr($userAgent, 0, 512) : null,
        ]);

        return [
            'plain' => $plain,
            'record' => $record,
        ];
    }

    /**
     * Validate and rotate an opaque refresh token, with family-wide reuse detection.
     *
     * @return array{status: 'ok'|'invalid'|'reused', jwt?: string, plain?: string, user?: User, activity?: LoginActivity}
     */
    public function rotate(string $plainToken, ?Request $request = null): array
    {
        $req = $request ?? request();
        $tokenHash = hash('sha256', trim($plainToken));

        return DB::transaction(function () use ($tokenHash, $req): array {
            $record = RefreshToken::where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return ['status' => 'invalid'];
            }

            // Reuse detection: token was already rotated or revoked -> compromise suspected!
            if (! is_null($record->rotated_at) || ! is_null($record->revoked_at)) {
                $this->revokeFamily($record->family_id, $record->user_id, $req);

                return [
                    'status' => 'reused',
                    'user' => $record->user,
                ];
            }

            if ($record->expires_at->isPast()) {
                $record->update(['revoked_at' => now()]);

                return ['status' => 'invalid'];
            }

            $user = User::find($record->user_id);
            if (! $user || $user->is_suspended || $user->trashed()) {
                $record->update(['revoked_at' => now()]);

                return ['status' => 'invalid'];
            }

            $activity = $record->login_activity_id
                ? LoginActivity::where('id', $record->login_activity_id)->where('user_id', $user->id)->first()
                : null;

            if (! $activity || ! $activity->is_active) {
                $record->update(['revoked_at' => now()]);

                return ['status' => 'invalid'];
            }

            $rollingMinutes = (int) (config('jwt.rolling_window') ?? 43200);
            if ($activity->updated_at && $activity->updated_at->lt(now()->subMinutes($rollingMinutes))) {
                $activity->update(['is_active' => false]);
                $record->update(['revoked_at' => now()]);

                return ['status' => 'invalid'];
            }

            $newJwt = auth('api')->login($user);
            $newJti = auth('api')->setToken($newJwt)->getPayload()->get('jti');

            $record->update(['rotated_at' => now()]);

            $activity->update([
                'token_id' => $newJti,
                'updated_at' => now(),
                'is_active' => true,
            ]);

            $issued = $this->issue($user, $activity, $newJti, $req, $record->family_id);

            return [
                'status' => 'ok',
                'jwt' => $newJwt,
                'plain' => $issued['plain'],
                'user' => $user,
                'activity' => $activity,
            ];
        });
    }

    public function revokeFamily(string $familyId, ?int $userId = null, ?Request $request = null): void
    {
        $tokens = RefreshToken::where('family_id', $familyId)->get();
        $activityIds = $tokens->pluck('login_activity_id')->filter()->unique()->values()->all();
        $jtis = $tokens->pluck('jti')->filter()->unique()->values()->all();

        RefreshToken::where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if (! empty($activityIds)) {
            $activityJtis = LoginActivity::whereIn('id', $activityIds)->pluck('token_id')->filter()->all();
            LoginActivity::whereIn('id', $activityIds)->update(['is_active' => false]);
            $jtis = array_unique(array_merge($jtis, $activityJtis));
        }

        $ttl = now()->addMinutes((int) (config('jwt.rolling_window') ?? 43200));
        foreach ($jtis as $jti) {
            if (is_string($jti) && $jti !== '') {
                Cache::put("revoked_jti:{$jti}", true, $ttl);
            }
        }

        $this->auditService->log(
            $userId,
            'refresh_token_reuse_detected',
            ['family_id' => $familyId, 'revoked_sessions' => count($activityIds)],
            'critical',
            $request,
        );
    }

    public function revokeByPlainToken(?string $plainToken): void
    {
        if (! is_string($plainToken) || trim($plainToken) === '') {
            return;
        }

        RefreshToken::where('token_hash', hash('sha256', trim($plainToken)))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * @param  array<int, int>  $activityIds
     */
    public function revokeForActivities(array $activityIds): void
    {
        if (empty($activityIds)) {
            return;
        }

        RefreshToken::whereIn('login_activity_id', $activityIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * @param  array<int, int>  $exceptActivityIds
     */
    public function revokeAllForUser(int $userId, array $exceptActivityIds = []): void
    {
        $query = RefreshToken::where('user_id', $userId)->whereNull('revoked_at');

        if (! empty($exceptActivityIds)) {
            $query->where(function ($q) use ($exceptActivityIds): void {
                $q->whereNotIn('login_activity_id', $exceptActivityIds)
                    ->orWhereNull('login_activity_id');
            });
        }

        $query->update(['revoked_at' => now()]);
    }

    public function attachRefreshCookie(Response $response, string $plainToken, ?Request $request = null): Response
    {
        $req = $request ?? request();
        $secure = $req->isSecure() || app()->isProduction();
        $minutes = max(60, (int) (config('jwt.refresh_ttl') ?? 43200));

        $cookie = Cookie::create(
            name: self::REFRESH_COOKIE_NAME,
            value: $plainToken,
            expire: now()->addMinutes($minutes),
            path: '/',
            domain: config('session.domain'),
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );

        $response->headers->setCookie($cookie);

        return $response;
    }

    public function clearRefreshCookie(Response $response, ?Request $request = null): Response
    {
        $req = $request ?? request();
        $secure = $req->isSecure() || app()->isProduction();

        $cookie = Cookie::create(
            name: self::REFRESH_COOKIE_NAME,
            value: '',
            expire: now()->subDay(),
            path: '/',
            domain: config('session.domain'),
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );

        $response->headers->setCookie($cookie);

        return $response;
    }
}
