<?php

namespace Tests\Feature\Api;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        Queue::fake();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_login_registers_fcm_token_for_the_user(): void
    {
        $user = $this->makeVerifiedUser();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'fcm_token' => 'device-token-abc',
        ])->assertOk();

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'device-token-abc',
        ]);
    }

    public function test_login_transfers_fcm_token_to_the_new_account_on_the_same_device(): void
    {
        $firstUser = $this->makeVerifiedUser();
        $secondUser = $this->makeVerifiedUser();

        $this->postJson('/api/login', [
            'email' => $firstUser->email,
            'password' => 'password',
            'fcm_token' => 'shared-device-token',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => $secondUser->email,
            'password' => 'password',
            'fcm_token' => 'shared-device-token',
        ])->assertOk();

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $secondUser->id,
            'fcm_token' => 'shared-device-token',
        ]);

        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $firstUser->id,
            'fcm_token' => 'shared-device-token',
        ]);

        $this->assertSame(1, FcmToken::where('fcm_token', 'shared-device-token')->count());
    }

    public function test_repeated_login_with_the_same_token_keeps_a_single_token_record(): void
    {
        $user = $this->makeVerifiedUser();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'fcm_token' => 'stable-device-token',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'fcm_token' => 'stable-device-token',
        ])->assertOk();

        $this->assertSame(1, FcmToken::where('user_id', $user->id)->count());
        $this->assertSame(1, FcmToken::where('fcm_token', 'stable-device-token')->count());
    }

    public function test_logout_removes_the_fcm_token_of_the_logging_out_device(): void
    {
        $user = $this->makeVerifiedUser();

        FcmToken::create(['user_id' => $user->id, 'fcm_token' => 'device-token-a']);
        FcmToken::create(['user_id' => $user->id, 'fcm_token' => 'device-token-b']);

        $this->actingAs($user, 'api')->postJson('/api/logout', [
            'fcm_token' => 'device-token-a',
        ])->assertOk();

        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'device-token-a',
        ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'device-token-b',
        ]);
    }

    public function test_logout_without_fcm_token_keeps_other_device_tokens(): void
    {
        $user = $this->makeVerifiedUser();

        FcmToken::create(['user_id' => $user->id, 'fcm_token' => 'device-token-a']);

        $this->actingAs($user, 'api')->postJson('/api/logout')->assertOk();

        $this->assertSame(1, FcmToken::where('user_id', $user->id)->count());
    }

    private function makeVerifiedUser(): User
    {
        $user = User::factory()->create([
            'is_verified' => true,
            'password' => 'password',
        ]);
        $user->assignRole('user');

        return $user;
    }
}
