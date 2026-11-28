<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;

class SessionIssuanceService
{
    public function __construct(
        protected DeviceTrustService $deviceTrustService,
        protected RefreshTokenService $refreshTokenService,
    ) {}

    /**
     * Check if user account is deleted or suspended.
     * Returns a JsonResponse if the user cannot proceed, or null if healthy.
     */
    public function checkAccountStatus(User $user): ?JsonResponse
    {
        if ($user->trashed()) {
            $deletionLog = DeletedAccountLog::where('user_id', $user->id)->latest()->first();

            $daysRemaining = 0;
            if ($deletionLog) {
                $seconds = now()->diffInSeconds($deletionLog->permanent_delete_at, false);
                $daysRemaining = ceil($seconds / (60 * 60 * 24));
            }

            if ($daysRemaining <= 0) {
                $daysRemaining = 1;
            }

            return response()->json([
                'status' => 'pending_deletion',
                'days_left' => (int) $daysRemaining,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'message' => "Your account is scheduled for deletion in {$daysRemaining} days. Please restore it using your credentials.",
            ], 200);
        }

        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        return null;
    }

    /**
     * Issue an authenticated session for the given user, dispatching Login event with JTI,
     * binding a cryptographic device identifier, and creating a rotating refresh token.
     *
     * @return array{token: string, refresh_token: string, device_id: string, user: User, is_onboarding: bool, jti: ?string}
     */
    public function issueSession(User $user, ?string $token = null): array
    {
        $token ??= auth('api')->login($user);

        $payload = auth('api')->setToken($token)->getPayload();
        $tokenId = $payload->get('jti');

        $device = $this->deviceTrustService->resolveOrCreate(request());

        request()->attributes->set('resolved_device_id', $device['plain']);
        request()->attributes->set('resolved_device_hash', $device['hash']);
        request()->merge(['current_token_id' => $tokenId]);

        event(new Login('api', $user, false));

        $activity = $tokenId
            ? LoginActivity::where('user_id', $user->id)->where('token_id', $tokenId)->latest('id')->first()
            : null;

        $refresh = $this->refreshTokenService->issue($user, $activity, is_string($tokenId) ? $tokenId : null, request());

        $roleName = $user->getRoleNames()->first();
        $user->makeHidden('roles');
        $user->role = $roleName;

        return [
            'token' => $token,
            'refresh_token' => $refresh['plain'],
            'device_id' => $device['plain'],
            'user' => $user,
            'is_onboarding' => $user->profile()->exists(),
            'jti' => $tokenId,
        ];
    }

    /**
     * Format a standard JSON response containing the authenticated session and user,
     * attaching HttpOnly cookies for rotating refresh token and cryptographic device ID.
     *
     * @param  array<string, mixed>  $extra
     */
    public function respondWithSession(User $user, ?string $token = null, array $extra = []): JsonResponse
    {
        $session = $this->issueSession($user, $token);

        $response = response()->json(array_merge([
            'success' => true,
            'status' => true,
            'is_onboarding' => $session['is_onboarding'],
            'user' => $session['user'],
            'token' => $session['token'],
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $extra));

        $this->refreshTokenService->attachRefreshCookie($response, $session['refresh_token'], request());
        $this->deviceTrustService->attachDeviceCookie($response, $session['device_id'], request());

        return $response;
    }
}
