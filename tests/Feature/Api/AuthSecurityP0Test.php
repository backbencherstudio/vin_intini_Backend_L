<?php

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthSecurityP0Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_a01_already_verified_user_cannot_obtain_token_with_arbitrary_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'is_verified' => true,
        ]);
        $user->assignRole('user');

        $response = $this->postJson('/api/register/verify-otp', [
            'email' => 'verified@example.com',
            'otp' => '9999',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', false)
            ->assertJsonMissing(['token']);
    }

    public function test_a01_suspended_user_cannot_obtain_token_via_registration_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'is_verified' => false,
            'is_suspended' => true,
        ]);
        $user->assignRole('user');

        Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
            'otp' => '1234',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/register/verify-otp', [
            'email' => 'suspended@example.com',
            'otp' => '1234',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonMissing(['token']);
    }

    public function test_a01_valid_registration_verification_consumes_otp_and_issues_token(): void
    {
        $user = User::factory()->create([
            'email' => 'fresh@example.com',
            'is_verified' => false,
        ]);
        $user->assignRole('user');

        Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
            'otp' => '4321',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/register/verify-otp', [
            'email' => 'fresh@example.com',
            'otp' => '4321',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['token', 'user']);

        $user->refresh();
        $this->assertTrue((bool) $user->is_verified);
        $this->assertDatabaseMissing('otps', [
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
        ]);

        // Replay attempt fails because OTP was consumed
        $replay = $this->postJson('/api/register/verify-otp', [
            'email' => 'fresh@example.com',
            'otp' => '4321',
        ]);
        $replay->assertStatus(400)
            ->assertJsonMissing(['token']);
    }

    public function test_a04_revoked_session_fails_across_header_query_and_body_jwt_transports(): void
    {
        $user = User::factory()->create([
            'email' => 'session@example.com',
            'is_verified' => true,
        ]);
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);

        $token = auth('api')->login($user);
        $tokenId = auth('api')->setToken($token)->getPayload()->get('jti');

        $activity = LoginActivity::create([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'is_active' => true,
            'device' => 'TestDevice',
            'status' => 'Successful',
            'login_at' => now(),
        ]);

        // Valid bearer header succeeds
        $validHeader = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $validHeader->assertOk();

        // Now revoke the session
        $activity->update(['is_active' => false]);

        // 1. Header transport must fail
        $revokedHeader = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $revokedHeader->assertStatus(401)
            ->assertJsonPath('message', 'Your session has been revoked.');

        // 2. Query parameter transport must fail
        $revokedQuery = $this->getJson("/api/me?token={$token}");
        $revokedQuery->assertStatus(401)
            ->assertJsonPath('message', 'Your session has been revoked.');

        // 3. Request body transport must fail on a POST endpoint
        $revokedBody = $this->postJson('/api/logout', [
            'token' => $token,
        ]);
        $revokedBody->assertStatus(401)
            ->assertJsonPath('message', 'Your session has been revoked.');
    }

    public function test_a04_suspended_user_is_rejected_on_protected_endpoints(): void
    {
        $user = User::factory()->create([
            'email' => 'active_suspended@example.com',
            'is_verified' => true,
            'is_suspended' => true,
        ]);
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);

        $token = auth('api')->login($user);
        $tokenId = auth('api')->setToken($token)->getPayload()->get('jti');

        LoginActivity::create([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'is_active' => true,
            'device' => 'TestDevice',
            'status' => 'Successful',
            'login_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Your account has been suspended.');
    }

    public function test_a02_restore_fails_without_credentials_or_proof(): void
    {
        $user = User::factory()->create([
            'email' => 'restore_nopass@example.com',
            'is_verified' => true,
        ]);
        $user->delete();

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => 'Restore User',
            'user_email' => $user->email,
            'reason' => 'Testing',
            'requested_at' => now(),
            'permanent_delete_at' => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/account/restore', [
            'email' => $user->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_a02_restore_fails_for_suspended_account(): void
    {
        $user = User::factory()->create([
            'email' => 'restore_suspended@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'is_suspended' => true,
        ]);
        $user->delete();

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => 'Suspended Restore',
            'user_email' => $user->email,
            'reason' => 'Testing',
            'requested_at' => now(),
            'permanent_delete_at' => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/account/restore', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }

    public function test_a02_restore_fails_when_window_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'restore_expired@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $user->delete();

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => 'Expired Restore',
            'user_email' => $user->email,
            'reason' => 'Testing',
            'requested_at' => now()->subDays(31),
            'permanent_delete_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/account/restore', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The restoration period for this account has expired.');
    }

    public function test_a02_restore_succeeds_with_valid_password_or_proof(): void
    {
        $user = User::factory()->create([
            'email' => 'restore_success@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');
        $user->delete();

        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => 'Success Restore',
            'user_email' => $user->email,
            'reason' => 'Testing',
            'requested_at' => now(),
            'permanent_delete_at' => now()->addDays(30),
        ]);

        // Wrong password fails
        $wrong = $this->postJson('/api/account/restore', [
            'email' => $user->email,
            'password' => 'wrong-pass',
        ]);
        $wrong->assertStatus(401)
            ->assertJsonPath('success', false);

        // Correct password succeeds
        $response = $this->postJson('/api/account/restore', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $user->refresh();
        $this->assertFalse($user->trashed());
        $this->assertDatabaseMissing('deleted_account_logs', [
            'user_id' => $user->id,
        ]);
    }
}
