<?php

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_admin_can_login_via_dedicated_admin_login_endpoint(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $admin->assignRole('admin');

        $response = $this->postJson('/api/admin/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);

        $this->assertEquals('admin', $response->json('user.role'));

        // Verify token contains custom claims
        $token = $response->json('token');
        $payload = auth('api')->setToken($token)->getPayload();
        $this->assertEquals('admin', $payload->get('role'));
    }

    public function test_regular_user_cannot_login_via_dedicated_admin_login_endpoint(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');

        $response = $this->postJson('/api/admin/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Access denied. You do not have administrative privileges.');
    }

    public function test_invalid_credentials_receive_unauthorized(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_admin_with_2fa_receives_challenge_and_can_verify(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'admin2fa@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'recovery_email' => 'recovery@example.com',
            'recovery_email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // 1. Initial admin login produces 2FA challenge
        $loginResponse = $this->postJson('/api/admin/login', [
            'email' => 'admin2fa@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('status', '2fa_required')
            ->assertJsonPath('two_factor_enabled', true)
            ->assertJsonStructure(['challenge_token', 'email']);

        $challengeToken = $loginResponse->json('challenge_token');
        $this->assertNotEmpty($challengeToken);

        // 2. Submit 2FA verification with valid authenticator OTP
        $validOtp = $google2fa->getCurrentOtp($secret);
        $verifyResponse = $this->postJson('/api/2fa/verify-login', [
            'email' => 'admin2fa@example.com',
            'challenge_token' => $challengeToken,
            'code' => $validOtp,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user']);

        $this->assertEquals('admin', $verifyResponse->json('user.role'));

        // Verify token custom claim
        $token = $verifyResponse->json('token');
        $payload = auth('api')->setToken($token)->getPayload();
        $this->assertEquals('admin', $payload->get('role'));
    }

    public function test_admin_can_reset_password_via_otp_flow(): void
    {
        Notification::fake();

        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'adminforgot@example.com',
            'password' => bcrypt('oldpassword123'),
            'is_verified' => true,
        ]);
        $admin->assignRole('admin');

        // 1. Send OTP
        $sendResponse = $this->postJson('/api/send-otp', [
            'email' => 'adminforgot@example.com',
        ]);
        $sendResponse->assertStatus(200)->assertJsonPath('status', true);

        $otpRecord = Otp::where('user_id', $admin->id)
            ->where('type', OtpType::PASSWORD_RESET->value)
            ->first();
        $this->assertNotNull($otpRecord);

        // 2. Verify OTP
        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => 'adminforgot@example.com',
            'otp' => (string) $otpRecord->otp,
        ]);
        $verifyResponse->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['reset_token']);

        $resetToken = $verifyResponse->json('reset_token');

        // 3. Reset Password
        $resetResponse = $this->postJson('/api/password-reset', [
            'email' => 'adminforgot@example.com',
            'reset_token' => $resetToken,
            'new_password' => 'newadminpassword123',
            'new_password_confirmation' => 'newadminpassword123',
        ]);
        $resetResponse->assertStatus(200)->assertJsonPath('status', true);

        // 4. Verify admin can login with new password
        $newLogin = $this->postJson('/api/admin/login', [
            'email' => 'adminforgot@example.com',
            'password' => 'newadminpassword123',
        ]);
        $newLogin->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_cannot_login_via_standard_user_login_endpoint(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'adminblock@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $admin->assignRole('admin');

        $response = $this->postJson('/api/login', [
            'email' => 'adminblock@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Administrative accounts are not permitted to log in via the user portal. Please sign in through the Admin Portal.');
    }

    public function test_regular_user_cannot_view_admin_profile_or_data_by_id(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'adminshielded@example.com',
            'username' => 'adminshielded',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $admin->assignRole('admin');
        \App\Models\UserProfile::create([
            'user_id' => $admin->id,
            'privacy_profile_visibility' => 'everyone',
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'studentviewer@example.com',
            'username' => 'studentviewer',
            'password' => bcrypt('password123'),
            'is_verified' => true,
        ]);
        $user->assignRole('user');
        \App\Models\UserProfile::create([
            'user_id' => $user->id,
            'privacy_profile_visibility' => 'everyone',
        ]);

        // 1. Regular user trying to view admin profile by ID
        $profileById = $this->actingAs($user, 'api')->getJson("/api/profile/{$admin->id}");
        $profileById->assertStatus(404)
            ->assertJsonPath('message', 'User profile not found');

        // 2. Regular user trying to view admin profile by username
        $profileByUsername = $this->actingAs($user, 'api')->getJson("/api/profile/{$admin->username}");
        $profileByUsername->assertStatus(404)
            ->assertJsonPath('message', 'User profile not found');

        // 3. Regular user trying to view admin experiences
        $expResponse = $this->actingAs($user, 'api')->getJson("/api/user-experiences/{$admin->id}");
        $expResponse->assertStatus(404);

        // 4. Regular user trying to view admin educations
        $eduResponse = $this->actingAs($user, 'api')->getJson("/api/user-educations/{$admin->id}");
        $eduResponse->assertStatus(404);

        // 5. Admin is excluded from user connection suggestions
        $suggestionsResponse = $this->actingAs($user, 'api')->getJson('/api/connections/suggestions');
        $suggestionsResponse->assertStatus(200);
        $suggestedIds = collect($suggestionsResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($admin->id, $suggestedIds);

        // 6. Regular user cannot send connection request to admin
        $connectResponse = $this->actingAs($user, 'api')->postJson('/api/connections/request', [
            'user_id' => $admin->id,
        ]);
        $connectResponse->assertStatus(422);

        // 7. Regular user cannot follow admin
        $followResponse = $this->actingAs($user, 'api')->postJson("/api/connections/{$admin->id}/follow");
        $followResponse->assertStatus(422);

        // 8. Regular user cannot initiate direct conversation with admin
        $conversationResponse = $this->actingAs($user, 'api')->postJson("/api/conversations/with/{$admin->id}");
        $conversationResponse->assertStatus(403)
            ->assertJsonPath('message', 'Direct messaging with administrative accounts is not permitted.');
    }
}
