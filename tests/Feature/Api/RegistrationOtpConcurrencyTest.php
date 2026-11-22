<?php

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\RegisterOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationOtpConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_multiple_concurrent_users_from_same_ip_can_register_and_receive_otp(): void
    {
        Notification::fake();

        // Simulate 8 distinct users registering in rapid succession from the exact same IP
        for ($i = 1; $i <= 8; $i++) {
            $email = "concurrent_user_{$i}@example.com";

            $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
                ->postJson('/api/register', [
                    'email' => $email,
                    'password' => 'Password123!',
                    'terms' => true,
                ]);

            $response->assertStatus(201)
                ->assertJsonPath('status', true)
                ->assertJsonPath('message', 'Registration successful. OTP sent to your email.');

            $this->assertDatabaseHas('users', [
                'email' => $email,
                'is_verified' => false,
            ]);

            $user = User::where('email', $email)->first();
            $this->assertNotNull($user);

            $this->assertDatabaseHas('otps', [
                'user_id' => $user->id,
                'type' => OtpType::REGISTRATION->value,
            ]);

            Notification::assertSentTo($user, RegisterOtpNotification::class);
        }
    }

    public function test_same_email_is_throttled_after_excessive_attempts(): void
    {
        Notification::fake();

        $email = 'spam_target@example.com';

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
                ->postJson('/api/register', [
                    'email' => $email,
                    'password' => 'Password123!',
                    'terms' => true,
                ]);

            $this->assertContains($response->getStatusCode(), [200, 201]);
        }

        // 6th attempt on the same email should be blocked by per-email rate limiter
        $blockedResponse = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->postJson('/api/register', [
                'email' => $email,
                'password' => 'Password123!',
                'terms' => true,
            ]);

        $blockedResponse->assertStatus(429);
    }

    public function test_resend_otp_allows_fresh_otp_after_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'resend_test@example.com',
            'is_verified' => false,
        ]);
        $user->assignRole('user');

        // Create an OTP that was sent 70 seconds ago (cooldown expired)
        $otp = Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
            'otp' => '1111',
            'expires_at' => now()->addMinutes(3),
        ]);

        DB::table('otps')->where('id', $otp->id)->update([
            'created_at' => now()->subSeconds(70),
            'updated_at' => now()->subSeconds(70),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->postJson('/api/register/resend-otp', [
                'email' => 'resend_test@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'OTP resent to your email.');

        Notification::assertSentTo($user, RegisterOtpNotification::class);
    }

    public function test_resend_otp_rejects_requests_within_60_second_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'cooldown_test@example.com',
            'is_verified' => false,
        ]);
        $user->assignRole('user');

        // OTP created 10 seconds ago
        Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::REGISTRATION->value,
            'otp' => '2222',
            'expires_at' => now()->addMinutes(3),
            'created_at' => now()->subSeconds(10),
            'updated_at' => now()->subSeconds(10),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->postJson('/api/register/resend-otp', [
                'email' => 'cooldown_test@example.com',
            ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', false);
    }

    public function test_notification_has_after_commit_enabled(): void
    {
        $notification = new RegisterOtpNotification('1234');
        $this->assertTrue((bool) $notification->afterCommit);
        $this->assertEquals(3, $notification->tries);
        $this->assertEquals(3, $notification->backoff);
    }
}
