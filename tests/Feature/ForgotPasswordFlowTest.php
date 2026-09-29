<?php

namespace Tests\Feature;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_after_otp_is_verified_without_sending_otp_again(): void
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
            'verified_at' => Carbon::now()->subSeconds(10),
        ]);

        $response = $this->postJson('/api/password-reset', [
            'email' => $user->email,
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
    }

    public function test_user_cannot_reset_password_without_verified_otp_state(): void
    {
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Otp::create([
            'user_id' => $user->id,
            'type' => OtpType::PASSWORD_RESET->value,
            'otp' => '5678',
            'expires_at' => Carbon::now()->addMinutes(2),
            'verified_at' => null,
        ]);

        $response = $this->postJson('/api/password-reset', [
            'email' => $user->email,
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'OTP verification required or expired');

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }
}
