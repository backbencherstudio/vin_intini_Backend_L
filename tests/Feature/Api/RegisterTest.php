<?php

namespace Tests\Feature\Api;

use App\Mail\RegisterOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_new_user_registers_with_email_and_password(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'secret123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'title' => 'Software Engineer',
            'mobile' => '01712345678',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'is_verified' => false,
        ]);

        Mail::assertQueued(RegisterOtpMail::class);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('user'));
        $this->assertNotNull($user->otp);
        $this->assertNull($user->first_name);
        $this->assertNull($user->mobile);
    }

    public function test_registration_resends_otp_for_existing_unverified_user(): void
    {
        Mail::fake();

        $user = User::create([
            'email' => 'john@example.com',
            'password' => bcrypt('oldpass123'),
            'first_name' => 'Old',
            'is_verified' => false,
        ]);

        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'newpass123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true);

        $user->refresh();

        $this->assertNotEquals('oldpass123', $user->password);
        $this->assertFalse($user->is_verified);
        $this->assertNotNull($user->otp);
    }
}
