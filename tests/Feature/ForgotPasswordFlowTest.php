<?php

namespace Tests\Feature;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_otp_and_reset_password_with_reset_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::PASSWORD_RESET->value,
            'otp' => '1234',
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);

        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'otp' => '1234',
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['reset_token']);

        $resetToken = $verifyResponse->json('reset_token');
        $this->assertNotEmpty($resetToken);

        $response = $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Password reset successfully');

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertDatabaseMissing('otps', [
            'user_id' => $user->id,
            'type' => OtpType::PASSWORD_RESET->value,
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        // Token reuse fails
        $reused = $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'new_password' => 'another-password',
            'new_password_confirmation' => 'another-password',
        ]);
        $reused->assertStatus(400);
    }

    public function test_user_cannot_reset_password_without_token_or_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_caller_b_cannot_reset_caller_a_account(): void
    {
        $userA = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('victim-password'),
        ]);

        $userB = User::factory()->create([
            'email' => 'attacker@example.com',
            'password' => Hash::make('attacker-password'),
        ]);

        // Attacker generates token for attacker's own account
        $tokenB = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $userB->email,
            'token' => Hash::make($tokenB),
            'created_at' => Carbon::now(),
        ]);

        // Attacker tries to use attacker's token to reset Victim's password
        $response = $this->postJson('/api/password-reset', [
            'email' => $userA->email,
            'reset_token' => $tokenB,
            'new_password' => 'hacked-password',
            'new_password_confirmation' => 'hacked-password',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', false);

        $userA->refresh();
        $this->assertTrue(Hash::check('victim-password', $userA->password));
    }

    public function test_reset_password_with_expired_token_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()->subMinutes(20),
        ]);

        $response = $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'reset_token' => $token,
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Password reset token has expired.');
    }
}
