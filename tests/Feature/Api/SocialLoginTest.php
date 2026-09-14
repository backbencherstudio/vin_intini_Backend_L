<?php

namespace Tests\Feature\Api;

use App\Models\DeletedAccountLog;
use App\Models\FcmToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_callback_registers_fcm_token_passed_in_the_state_for_a_new_account(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-test-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => '',
        ]));

        $this->getJson('/api/auth/google/callback?state='.urlencode('platform=app&fcm_token=social-device-token'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'social-device-token',
        ]);
    }

    public function test_callback_moves_fcm_token_to_the_google_account_on_the_same_device(): void
    {
        $existingUser = User::factory()->create(['is_verified' => true]);
        FcmToken::create(['user_id' => $existingUser->id, 'fcm_token' => 'social-device-token']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-test-456',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'avatar' => '',
        ]));

        $this->getJson('/api/auth/google/callback?state='.urlencode('platform=app&fcm_token=social-device-token'))
            ->assertOk();

        $jane = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($jane);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $jane->id,
            'fcm_token' => 'social-device-token',
        ]);

        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $existingUser->id,
            'fcm_token' => 'social-device-token',
        ]);

        $this->assertSame(1, FcmToken::where('fcm_token', 'social-device-token')->count());
    }

    public function test_social_login_registers_fcm_token_for_the_user(): void
    {
        $provider = new class implements Provider
        {
            public function redirect()
            {
                return new RedirectResponse('https://example.test');
            }

            public function user()
            {
                return SocialiteUser::fake([
                    'id' => 'google-social-login-1',
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'avatar' => '',
                ]);
            }

            public function userFromToken($token)
            {
                return $this->user();
            }

            public function userByIdentityToken($token, $nonce = null)
            {
                return $this->user();
            }
        };

        Socialite::extend('google', fn () => $provider);

        $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'access_token' => 'google-access-token',
            'fcm_token' => 'social-login-device-token',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'social-login-device-token',
        ]);
    }

    public function test_callback_returns_pending_deletion_instead_of_restoring_a_deleted_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $user->delete();
        DeletedAccountLog::create([
            'user_id' => $user->id,
            'user_name' => 'Jane Doe',
            'user_email' => 'jane@example.com',
            'reason' => 'testing',
            'requested_at' => now(),
            'permanent_delete_at' => Carbon::now()->addDays(30),
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-test-789',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'avatar' => '',
        ]));

        $this->getJson('/api/auth/google/callback?state='.urlencode('platform=app'))
            ->assertOk()
            ->assertJsonPath('status', 'pending_deletion')
            ->assertJsonPath('email', 'jane@example.com')
            ->assertJsonMissingPath('data.token');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('deleted_account_logs', ['user_id' => $user->id]);
    }
}
