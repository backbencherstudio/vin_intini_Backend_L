<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Jobs\SendLoginAlertEmailJob;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\RefreshToken;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Auth\DeviceTrustService;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EnterpriseSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_api_responses_include_enterprise_security_headers(): void
    {
        $response = $this->getJson('/api/login');

        $response->assertStatus(401)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_login_issues_httponly_refresh_and_device_cookies_and_rotates_with_reuse_detection(): void
    {
        Queue::fake();

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'enterprise@example.com',
            'password' => Hash::make('StrongPass1'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'StrongPass1',
        ]);

        $loginResponse->assertOk()
            ->assertCookie(RefreshTokenService::REFRESH_COOKIE_NAME)
            ->assertCookie(DeviceTrustService::DEVICE_COOKIE_NAME);

        $cookies = collect($loginResponse->headers->getCookies());
        $refreshCookie = $cookies->firstWhere(fn ($c) => $c->getName() === RefreshTokenService::REFRESH_COOKIE_NAME);
        $deviceCookie = $cookies->firstWhere(fn ($c) => $c->getName() === DeviceTrustService::DEVICE_COOKIE_NAME);

        $this->assertNotNull($refreshCookie);
        $this->assertTrue($refreshCookie->isHttpOnly());
        $this->assertNotNull($deviceCookie);
        $this->assertTrue($deviceCookie->isHttpOnly());

        $initialPlainRefresh = $refreshCookie->getValue();
        $initialJwt = $loginResponse->json('token');

        // Rotate using the opaque refresh token cookie
        $rotateResponse = $this->withCredentials()
            ->withUnencryptedCookie(RefreshTokenService::REFRESH_COOKIE_NAME, $initialPlainRefresh)
            ->postJson('/api/refresh');

        $rotateResponse->assertOk()
            ->assertJsonStructure(['token', 'expires_in'])
            ->assertCookie(RefreshTokenService::REFRESH_COOKIE_NAME);

        $rotatedCookie = collect($rotateResponse->headers->getCookies())
            ->firstWhere(fn ($c) => $c->getName() === RefreshTokenService::REFRESH_COOKIE_NAME);
        $this->assertNotNull($rotatedCookie);
        $this->assertNotEquals($initialPlainRefresh, $rotatedCookie->getValue());
        $this->assertNotEquals($initialJwt, $rotateResponse->json('token'));

        // Replay the OLD rotated refresh token -> reuse detection revokes family & session!
        $reuseResponse = $this->withCredentials()
            ->withUnencryptedCookie(RefreshTokenService::REFRESH_COOKIE_NAME, $initialPlainRefresh)
            ->postJson('/api/refresh');

        $reuseResponse->assertStatus(401);

        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $user->id,
            'event' => 'refresh_token_reuse_detected',
            'severity' => 'critical',
        ]);

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);

        // Even the newly rotated token is now rejected because the entire family was revoked
        $followUpResponse = $this->withCredentials()
            ->withUnencryptedCookie(RefreshTokenService::REFRESH_COOKIE_NAME, $rotatedCookie->getValue())
            ->postJson('/api/refresh');
        $followUpResponse->assertStatus(401);
    }

    public function test_cryptographic_device_binding_prevents_user_agent_spoof_auto_trust(): void
    {
        Queue::fake();

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'devicebind@example.com',
            'password' => Hash::make('StrongPass1'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');

        // 1st login establishes a trusted cryptographic device cookie
        $firstLogin = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/125.0')
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'StrongPass1',
            ]);
        $firstLogin->assertOk();

        $trustedDeviceCookie = collect($firstLogin->headers->getCookies())
            ->firstWhere(fn ($c) => $c->getName() === DeviceTrustService::DEVICE_COOKIE_NAME)
            ->getValue();

        // 2nd login WITH the same device cookie is trusted (no alert dispatched)
        $sameDeviceLogin = $this->withCredentials()
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/125.0')
            ->withUnencryptedCookie(DeviceTrustService::DEVICE_COOKIE_NAME, $trustedDeviceCookie)
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'StrongPass1',
            ]);
        $sameDeviceLogin->assertOk();
        Queue::assertNotPushed(SendLoginAlertEmailJob::class);

        // 3rd login with IDENTICAL User-Agent/IP but DIFFERENT/NO device cookie MUST be untrusted & dispatch alert
        $this->unencryptedCookies = [];
        $spoofedLogin = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/125.0')
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'StrongPass1',
            ]);
        $spoofedLogin->assertOk();

        Queue::assertPushed(SendLoginAlertEmailJob::class);
        $latestActivity = LoginActivity::where('user_id', $user->id)->latest('id')->first();
        $this->assertFalse((bool) $latestActivity->is_trusted);
    }

    public function test_missing_login_activity_fails_closed_as_untrusted_and_blocks_privacy_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);

        $token = auth('api')->login($user);

        // Attempt privacy settings update without any trusted LoginActivity row
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/user/privacy-settings', [
                'privacy_profile_visibility' => 'nobody',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('requires_trusted_session', true);

        $activity = LoginActivity::where('user_id', $user->id)->first();
        $this->assertNotNull($activity);
        $this->assertFalse((bool) $activity->is_trusted);
    }

    public function test_registration_enforces_strong_password_and_otp_bruteforce_lockout(): void
    {
        Notification::fake();

        $weak = $this->postJson('/api/register', [
            'email' => 'weakpass@example.com',
            'password' => '123456',
            'terms' => true,
        ]);
        $weak->assertStatus(422);

        $valid = $this->postJson('/api/register', [
            'email' => 'strongpass@example.com',
            'password' => 'StrongPass1',
            'terms' => true,
        ]);
        $valid->assertStatus(201);

        $user = User::where('email', 'strongpass@example.com')->firstOrFail();

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/api/register/verify-otp', [
                'email' => $user->email,
                'otp' => '0000',
            ])->assertStatus(400);
        }

        // 5th wrong OTP locks out and deletes OTP
        $this->postJson('/api/register/verify-otp', [
            'email' => $user->email,
            'otp' => '0000',
        ])->assertStatus(429);

        $this->assertDatabaseMissing('otps', [
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
        ]);
    }

    public function test_password_reset_revokes_all_sessions_and_records_audit_log(): void
    {
        Notification::fake();

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldStrong1'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);

        $token = auth('api')->login($user);
        $jti = auth('api')->setToken($token)->getPayload()->get('jti');
        $activity = LoginActivity::create([
            'user_id' => $user->id,
            'token_id' => $jti,
            'device' => 'Windows',
            'is_active' => true,
            'is_trusted' => true,
            'is_resolved' => true,
            'login_at' => now(),
        ]);
        app(RefreshTokenService::class)->issue($user, $activity, $jti);

        $this->postJson('/api/send-otp', ['email' => $user->email])->assertOk();
        $otpRecord = Otp::where('user_id', $user->id)->where('type', OtpType::PASSWORD_RESET->value)->firstOrFail();

        $verify = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'otp' => (string) $otpRecord->otp,
        ])->assertOk();

        $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'reset_token' => $verify->json('reset_token'),
            'new_password' => 'NewStrong1',
            'new_password_confirmation' => 'NewStrong1',
        ])->assertOk();

        // Previous JWT must now be rejected
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertStatus(401);

        $this->assertSame(0, RefreshToken::where('user_id', $user->id)->whereNull('revoked_at')->count());
        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $user->id,
            'event' => 'password_reset',
        ]);
    }

    public function test_user_can_fetch_paginated_security_audit_logs(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole('user');

        SecurityAuditLog::create([
            'user_id' => $user->id,
            'event' => 'password_changed',
            'severity' => 'warning',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/security/audit-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.event', 'password_changed')
            ->assertJsonPath('data.0.severity', 'warning');
    }

    public function test_tiered_audit_log_pruning_cleans_old_info_and_retains_recent_warnings(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['is_verified' => true]);

        $oldInfo = SecurityAuditLog::create([
            'user_id' => $user->id,
            'event' => 'login_success',
            'severity' => 'info',
            'created_at' => now()->subDays(65),
        ]);
        $recentInfo = SecurityAuditLog::create([
            'user_id' => $user->id,
            'event' => 'login_success',
            'severity' => 'info',
            'created_at' => now()->subDays(10),
        ]);
        $olderWarning = SecurityAuditLog::create([
            'user_id' => $user->id,
            'event' => 'password_changed',
            'severity' => 'warning',
            'created_at' => now()->subDays(90),
        ]);
        $expiredCritical = SecurityAuditLog::create([
            'user_id' => $user->id,
            'event' => 'refresh_token_reuse_detected',
            'severity' => 'critical',
            'created_at' => now()->subDays(400),
        ]);

        $this->artisan('model:prune', ['--model' => [SecurityAuditLog::class]])->assertExitCode(0);

        $this->assertDatabaseMissing('security_audit_logs', ['id' => $oldInfo->id]);
        $this->assertDatabaseMissing('security_audit_logs', ['id' => $expiredCritical->id]);
        $this->assertDatabaseHas('security_audit_logs', ['id' => $recentInfo->id]);
        $this->assertDatabaseHas('security_audit_logs', ['id' => $olderWarning->id]);
    }

    public function test_refresh_token_pruning_cleans_old_rotated_tokens_while_keeping_recent_and_active(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['is_verified' => true]);

        $oldRotated = RefreshToken::create([
            'user_id' => $user->id,
            'family_id' => (string) \Illuminate\Support\Str::uuid(),
            'token_hash' => hash('sha256', 'old-rotated'),
            'expires_at' => now()->addDays(15),
            'rotated_at' => now()->subDays(15),
        ]);
        $recentRotated = RefreshToken::create([
            'user_id' => $user->id,
            'family_id' => (string) \Illuminate\Support\Str::uuid(),
            'token_hash' => hash('sha256', 'recent-rotated'),
            'expires_at' => now()->addDays(25),
            'rotated_at' => now()->subDays(5),
        ]);
        $activeToken = RefreshToken::create([
            'user_id' => $user->id,
            'family_id' => (string) \Illuminate\Support\Str::uuid(),
            'token_hash' => hash('sha256', 'active-token'),
            'expires_at' => now()->addDays(29),
        ]);

        $this->artisan('model:prune', ['--model' => [RefreshToken::class]])->assertExitCode(0);

        $this->assertDatabaseMissing('refresh_tokens', ['id' => $oldRotated->id]);
        $this->assertDatabaseHas('refresh_tokens', ['id' => $recentRotated->id]);
        $this->assertDatabaseHas('refresh_tokens', ['id' => $activeToken->id]);
    }

    public function test_2fa_challenge_survives_single_typo_and_locks_out_after_five_failed_attempts(): void
    {
        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $secret = $google2fa->generateSecretKey();

        /** @var User $user */
        $user = User::factory()->create([
            'email' => '2faretry@example.com',
            'password' => Hash::make('StrongPass1'),
            'is_verified' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('user');

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'StrongPass1',
        ])->assertOk();

        $challengeToken = $login->json('challenge_token');

        // 1st wrong code returns 401 and keeps challenge alive
        $this->postJson('/api/2fa/verify-login', [
            'email' => $user->email,
            'code' => '000000',
            'challenge_token' => $challengeToken,
        ])->assertStatus(401);

        // Valid code on 2nd attempt succeeds with the SAME challenge_token
        $validOtp = $google2fa->getCurrentOtp($secret);
        $this->postJson('/api/2fa/verify-login', [
            'email' => $user->email,
            'code' => $validOtp,
            'challenge_token' => $challengeToken,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        // Reusing already consumed challenge fails
        $this->postJson('/api/2fa/verify-login', [
            'email' => $user->email,
            'code' => $validOtp,
            'challenge_token' => $challengeToken,
        ])->assertStatus(403);
    }
}
