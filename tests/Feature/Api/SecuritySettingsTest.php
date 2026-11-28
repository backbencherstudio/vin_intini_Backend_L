<?php

namespace Tests\Feature\Api;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected string $jti;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->user = User::factory()->create([
            'is_verified' => true,
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('user');

        $this->token = auth('api')->login($this->user);
        $this->jti = auth('api')->setToken($this->token)->getPayload()->get('jti');

        LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => $this->jti,
            'device' => 'Desktop (Windows)',
            'browser' => 'Chrome',
            'ip_address' => '127.0.0.1',
            'location' => 'Dhaka, Bangladesh',
            'status' => 'Successful',
            'is_active' => true,
            'is_trusted' => true,
            'is_resolved' => true,
            'login_at' => now(),
        ]);
    }

    public function test_get_security_overview_returns_correct_structure(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/security/overview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'security_score' => ['percentage', 'rating', 'last_checked'],
                    'has_password',
                    'password_strength',
                    'two_factor_auth',
                    'active_sessions',
                    'account_recovery',
                    'login_activity',
                    'is_suspicious',
                    'can_resolve_from_here',
                    'two_factor_enabled',
                    'backup_codes_count',
                    'recovery_email',
                    'recovery_email_verified',
                ],
            ]);
    }

    public function test_get_active_sessions_returns_current_device_first(): void
    {
        LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'other-device-token',
            'device' => 'iPhone (iOS)',
            'browser' => 'Native Mobile App',
            'ip_address' => '10.0.0.2',
            'location' => 'Chittagong, Bangladesh',
            'status' => 'Successful',
            'is_active' => true,
            'is_trusted' => true,
            'is_resolved' => true,
            'login_at' => now()->subHour(),
        ]);

        $response = $this->withToken($this->token)->getJson('/api/security/active-sessions');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertCount(2, $items);
        $this->assertTrue($items[0]['is_current']);
        $this->assertSame('Current device', $items[0]['status']);
        $this->assertFalse($items[1]['is_current']);
    }

    public function test_cannot_revoke_current_active_session(): void
    {
        $currentActivity = LoginActivity::where('token_id', $this->jti)->first();

        $response = $this->withToken($this->token)->postJson('/api/security/sessions/revoke/'.$currentActivity->id);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You cannot revoke your current active session.');
    }

    public function test_can_revoke_other_active_session(): void
    {
        $otherActivity = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'other-session-to-revoke',
            'device' => 'MacBook Pro',
            'browser' => 'Safari',
            'ip_address' => '10.0.0.5',
            'status' => 'Successful',
            'is_active' => true,
            'login_at' => now(),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/security/sessions/revoke/'.$otherActivity->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Device has been signed out successfully.');

        $this->assertDatabaseHas('login_activities', [
            'id' => $otherActivity->id,
            'is_active' => false,
        ]);
    }

    public function test_sign_out_all_sessions_deactivates_other_and_null_token_sessions(): void
    {
        $otherSession = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'other-token-jwt',
            'device' => 'MacBook Pro',
            'browser' => 'Safari',
            'ip_address' => '10.0.0.5',
            'status' => 'Successful',
            'is_active' => true,
            'login_at' => now(),
        ]);

        $nullTokenSession = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => null,
            'device' => 'Legacy Device',
            'browser' => 'Firefox',
            'ip_address' => '10.0.0.6',
            'status' => 'Successful',
            'is_active' => true,
            'login_at' => now(),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/security/sessions/sign-out-all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'All other sessions have been signed out successfully.');

        // Current session stays active
        $this->assertDatabaseHas('login_activities', [
            'token_id' => $this->jti,
            'is_active' => true,
        ]);

        // Other session is deactivated
        $this->assertDatabaseHas('login_activities', [
            'id' => $otherSession->id,
            'is_active' => false,
        ]);

        // Null token session is also deactivated
        $this->assertDatabaseHas('login_activities', [
            'id' => $nullTokenSession->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_delete_current_login_activity(): void
    {
        $currentActivity = LoginActivity::where('token_id', $this->jti)->first();

        $response = $this->withToken($this->token)->deleteJson('/api/security/login-activities/'.$currentActivity->id);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You cannot delete your current active session.');
    }

    public function test_can_delete_other_login_activity(): void
    {
        $otherActivity = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'old-session-inactive',
            'device' => 'Old Phone',
            'browser' => 'Chrome',
            'status' => 'Successful',
            'is_active' => false,
            'login_at' => now()->subDays(5),
        ]);

        $response = $this->withToken($this->token)->deleteJson('/api/security/login-activities/'.$otherActivity->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login activity deleted successfully.');

        $this->assertDatabaseMissing('login_activities', ['id' => $otherActivity->id]);
    }

    public function test_delete_all_login_activities_preserves_current_session(): void
    {
        LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'to-delete-1',
            'is_active' => false,
            'login_at' => now()->subDay(),
        ]);
        LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => null,
            'is_active' => false,
            'login_at' => now()->subDay(),
        ]);

        $response = $this->withToken($this->token)->deleteJson('/api/security/login-activities/clear-all');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('login_activities', ['token_id' => $this->jti]);
        $this->assertDatabaseMissing('login_activities', ['token_id' => 'to-delete-1']);
    }

    public function test_resolve_suspicious_login_marks_trusted_and_resolved(): void
    {
        $suspiciousActivity = LoginActivity::create([
            'user_id' => $this->user->id,
            'device' => 'Untrusted Linux',
            'browser' => 'Firefox',
            'ip_address' => '1.2.3.4',
            'location' => 'Unknown City',
            'status' => 'Successful',
            'is_active' => true,
            'is_trusted' => false,
            'is_resolved' => false,
            'login_at' => now(),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/security/resolve/'.$suspiciousActivity->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Device marked as trusted.');

        $this->assertDatabaseHas('login_activities', [
            'id' => $suspiciousActivity->id,
            'is_trusted' => true,
            'is_resolved' => true,
        ]);
    }

    public function test_secure_account_blocks_activity(): void
    {
        $suspiciousActivity = LoginActivity::create([
            'user_id' => $this->user->id,
            'device' => 'Hacker PC',
            'browser' => 'Unknown',
            'ip_address' => '5.6.7.8',
            'location' => 'Remote Country',
            'status' => 'Successful',
            'is_active' => true,
            'is_trusted' => false,
            'is_resolved' => false,
            'login_at' => now(),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/security/secure-account/'.$suspiciousActivity->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('action_required', 'change_password');

        $this->assertDatabaseHas('login_activities', [
            'id' => $suspiciousActivity->id,
            'is_active' => false,
            'is_trusted' => false,
            'is_resolved' => true,
        ]);
    }

    public function test_untrusted_session_cannot_revoke_delete_trust_or_modify_security_settings(): void
    {
        $trustedActivity = LoginActivity::where('token_id', $this->jti)->firstOrFail();

        $attackerUser = $this->user;
        $untrustedToken = auth('api')->login($attackerUser);
        $untrustedJti = auth('api')->setToken($untrustedToken)->getPayload()->get('jti');

        $untrustedActivity = LoginActivity::create([
            'user_id' => $attackerUser->id,
            'token_id' => $untrustedJti,
            'device' => 'Unknown Linux',
            'browser' => 'Firefox',
            'ip_address' => '9.9.9.9',
            'location' => 'Unknown',
            'status' => 'Successful',
            'is_active' => true,
            'is_trusted' => false,
            'is_resolved' => false,
            'login_at' => now(),
        ]);

        // Overview indicates session is not trusted yet
        $this->withToken($untrustedToken)
            ->getJson('/api/security/overview')
            ->assertOk()
            ->assertJsonPath('data.is_current_session_trusted', false);

        // Cannot revoke owner's trusted session
        $this->withToken($untrustedToken)
            ->postJson('/api/security/sessions/revoke/'.$trustedActivity->id)
            ->assertStatus(403)
            ->assertJsonPath('requires_trusted_session', true);

        // Cannot sign out all sessions
        $this->withToken($untrustedToken)
            ->postJson('/api/security/sessions/sign-out-all')
            ->assertStatus(403);

        // Cannot delete or clear login activities
        $this->withToken($untrustedToken)
            ->deleteJson('/api/security/login-activities/'.$trustedActivity->id)
            ->assertStatus(403);

        $this->withToken($untrustedToken)
            ->deleteJson('/api/security/login-activities/clear-all')
            ->assertStatus(403);

        // Cannot mark itself or other suspicious logins as trusted
        $this->withToken($untrustedToken)
            ->postJson('/api/security/resolve/'.$untrustedActivity->id)
            ->assertStatus(403);

        $this->withToken($untrustedToken)
            ->postJson('/api/security/resolve-all')
            ->assertStatus(403);

        // Cannot change password, disable 2FA, or delete account
        $this->withToken($untrustedToken)
            ->postJson('/api/profile/change-password', [
                'current_password' => 'password',
                'new_password' => 'NewStrong123',
                'new_password_confirmation' => 'NewStrong123',
            ])
            ->assertStatus(403);

        $this->withToken($untrustedToken)
            ->postJson('/api/2fa/disable', ['password' => 'password'])
            ->assertStatus(403);

        $this->withToken($untrustedToken)
            ->postJson('/api/account/delete-request', [
                'reason' => 'Test',
                'password' => 'password',
            ])
            ->assertStatus(403);

        // Owner's trusted session must remain active
        $this->assertDatabaseHas('login_activities', [
            'id' => $trustedActivity->id,
            'is_active' => true,
            'is_trusted' => true,
        ]);
    }

    public function test_can_bulk_delete_selected_login_activities_preserving_current_session(): void
    {
        $currentActivity = LoginActivity::where('token_id', $this->jti)->firstOrFail();

        $activity1 = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'bulk-del-1',
            'is_active' => true,
            'login_at' => now()->subHours(2),
        ]);
        $activity2 = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'bulk-del-2',
            'is_active' => false,
            'login_at' => now()->subHours(3),
        ]);
        $activityKeep = LoginActivity::create([
            'user_id' => $this->user->id,
            'token_id' => 'bulk-keep',
            'is_active' => false,
            'login_at' => now()->subHours(4),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/security/login-activities/bulk-delete', [
            'ids' => [$currentActivity->id, $activity1->id, $activity2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseHas('login_activities', ['id' => $currentActivity->id]);
        $this->assertDatabaseHas('login_activities', ['id' => $activityKeep->id]);
        $this->assertDatabaseMissing('login_activities', ['id' => $activity1->id]);
        $this->assertDatabaseMissing('login_activities', ['id' => $activity2->id]);
    }
}
