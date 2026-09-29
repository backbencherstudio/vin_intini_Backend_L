<?php

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Mail\RecoveryOtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TwoFactorOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_user_can_request_and_confirm_recovery_email_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'primary@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/2fa/recovery-email/update', [
                'email' => 'recovery@example.com',
                'password' => 'secret123',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        Mail::assertQueued(RecoveryOtpMail::class);

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::RECOVERY_EMAIL->value)
            ->first();

        $this->assertNotNull($otpRecord);
        $this->assertEquals(OtpType::RECOVERY_EMAIL, $otpRecord->type);

        $confirmResponse = $this->actingAs($user, 'api')
            ->postJson('/api/2fa/recovery-email/confirm', [
                'otp' => (string) $otpRecord->otp,
            ]);

        $confirmResponse->assertOk()
            ->assertJsonPath('status', true);

        $user->refresh();
        $this->assertNotNull($user->recovery_email_verified_at);
        $this->assertDatabaseMissing('otps', [
            'user_id' => $user->id,
            'type' => OtpType::RECOVERY_EMAIL->value,
        ]);
    }

    public function test_user_can_receive_and_verify_recovery_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'account@example.com',
            'recovery_email' => 'backup@example.com',
            'recovery_email_verified_at' => now(),
        ]);
        $user->assignRole('user');

        $sendResponse = $this->postJson('/api/2fa/recovery-send-otp', [
            'email' => $user->email,
            'recovery_email' => $user->recovery_email,
        ]);

        $sendResponse->assertOk()
            ->assertJsonPath('status', true);

        Mail::assertQueued(RecoveryOtpMail::class);

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::RECOVERY->value)
            ->first();

        $this->assertNotNull($otpRecord);
        $this->assertEquals(OtpType::RECOVERY, $otpRecord->type);

        $verifyResponse = $this->postJson('/api/2fa/recovery-verify', [
            'email' => $user->email,
            'otp' => (string) $otpRecord->otp,
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseMissing('otps', [
            'user_id' => $user->id,
            'type' => OtpType::RECOVERY->value,
        ]);
    }
}
