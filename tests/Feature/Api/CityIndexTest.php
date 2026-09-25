<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\State;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CityIndexTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);
        UserProfile::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_cities_are_listed_for_a_state(): void
    {
        $state = State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'texas']);
        City::create(['state_id' => $state->id, 'name' => 'Dallas']);
        City::create(['state_id' => $state->id, 'name' => 'Austin']);
        City::create(['state_id' => $state->id, 'name' => 'Houston']);

        $response = $this->actingAs($this->user(), 'api')->getJson('/api/states/TX/cities');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Cities retrieved successfully.')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('state.code', 'TX')
            ->assertJsonPath('data.0.name', 'Austin')
            ->assertJsonPath('data.1.name', 'Dallas')
            ->assertJsonPath('data.2.name', 'Houston');
    }

    public function test_cities_returns_404_for_unknown_state(): void
    {
        $this->actingAs($this->user(), 'api')
            ->getJson('/api/states/XX/cities')
            ->assertNotFound()
            ->assertJsonPath('message', 'State not found');
    }

    public function test_cities_requires_authentication(): void
    {
        $this->getJson('/api/states/TX/cities')->assertUnauthorized();
    }
}
