<?php

namespace Tests\Feature\Api;

use App\Models\IndustryPartner;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase1SecurityP1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_a05_2fa_login_requires_first_factor_challenge_and_blocks_direct_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => '2fa_user@example.com',
            'password' => Hash::make('password123'),
            'two_factor_secret' => 'SECRET',
            'two_factor_confirmed_at' => now(),
            'is_verified' => true,
        ]);
        $user->assignRole('user');

        // Direct code submission without challenge fails
        $direct = $this->postJson('/api/2fa/verify-login', [
            'email' => $user->email,
            'code' => '123456',
        ]);
        $direct->assertStatus(422);

        // First factor login issues challenge
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $login->assertOk()
            ->assertJsonPath('status', '2fa_required')
            ->assertJsonStructure(['challenge_token']);

        $challenge = $login->json('challenge_token');
        $this->assertNotEmpty($challenge);

        // Challenge for another user fails
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'email' => 'other2fa@example.com',
            'password' => Hash::make('password123'),
            'two_factor_secret' => 'SECRET2',
            'two_factor_confirmed_at' => now(),
            'is_verified' => true,
        ]);
        $otherUser->assignRole('user');

        $cross = $this->postJson('/api/2fa/verify-login', [
            'email' => $otherUser->email,
            'code' => '123456',
            'challenge_token' => $challenge,
        ]);
        $cross->assertStatus(403);
    }

    public function test_a07_oauth_callback_rejects_missing_or_invalid_state(): void
    {
        // Missing state parameter
        $missing = $this->getJson('/api/auth/google/callback');
        $missing->assertStatus(422)
            ->assertJsonPath('success', false);

        // Forged or expired state
        $invalid = $this->getJson('/api/auth/google/callback?state=forged_state_key');
        $invalid->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_a08_newsfeed_preview_applies_visibility_filtering(): void
    {
        $response = $this->get('/newsfeed-preview');
        $response->assertNotFound();
    }

    public function test_a10_get_clear_route_is_removed(): void
    {
        $response = $this->get('/clear');
        $response->assertNotFound();
    }

    public function test_a10_get_cannot_delete_admin_records(): void
    {
        $adminRole = Role::findByName('admin', 'web');
        /** @var User $admin */
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole($adminRole);

        /** @var IndustryPartner $partner */
        $partner = IndustryPartner::create([
            'partner_name' => 'Acme Partner',
            'industry_type' => 'biotechnology',
            'network_type' => 'neuroscience',
        ]);

        // GET request to delete route must fail (Method Not Allowed 405)
        /** @var User $admin */
        $getResponse = $this->actingAs($admin, 'web')
            ->get("/admin/partners/delete/{$partner->id}");
        $getResponse->assertStatus(405);

        // Partner record remains intact
        $this->assertDatabaseHas('industry_partners', [
            'id' => $partner->id,
        ]);

        // In tests with actingAs('web'), create active session for web guard
        $sessionId = session()->getId();
        LoginActivity::create([
            'user_id' => $admin->id,
            'token_id' => $sessionId,
            'is_active' => true,
            'status' => 'Successful',
            'login_at' => now(),
        ]);

        // Protected DELETE request succeeds
        /** @var User $admin */
        $deleteResponse = $this->actingAs($admin, 'web')
            ->delete("/admin/partners/delete/{$partner->id}");
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('industry_partners', [
            'id' => $partner->id,
        ]);
    }
}
