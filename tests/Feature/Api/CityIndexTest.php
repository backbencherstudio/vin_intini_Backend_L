<?php

namespace Tests\Feature\Api;

use App\Enums\IndustryJobPostStatus;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobPost;
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

    public function test_states_counts_active_industry_jobs(): void
    {
        $user = $this->user();
        $state = State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'texas']);

        $industry = Industry::create([
            'created_by' => $user->id,
            'name' => 'Tech Corp',
            'slug' => 'tech-corp-'.uniqid(),
            'status' => 'approved',
        ]);

        // 1 Active published industry job
        IndustryJobPost::create([
            'job_id' => '111111',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Software Engineer',
            'slug' => 'software-engineer-'.uniqid(),
            'state_id' => $state->id,
            'status' => IndustryJobPostStatus::PUBLISHED,
            'email' => 'jobs@techcorp.com',
            'work_mode' => 'on_site',
            'employment_type' => 'full_time',
            'job_description' => 'Great job',
            'salary_type' => 'Yearly',
            'salary_min' => 80000,
            'salary_max' => 120000,
        ]);

        // 1 Expired job (should NOT be counted)
        IndustryJobPost::create([
            'job_id' => '222222',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Expired Engineer',
            'slug' => 'expired-engineer-'.uniqid(),
            'state_id' => $state->id,
            'status' => IndustryJobPostStatus::EXPIRED,
            'email' => 'jobs@techcorp.com',
            'work_mode' => 'on_site',
            'employment_type' => 'full_time',
            'job_description' => 'Expired job',
            'salary_type' => 'Yearly',
            'salary_min' => 80000,
            'salary_max' => 120000,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/states');
        $response->assertOk()
            ->assertJsonPath('success', true);

        $stateData = collect($response->json('data'))->firstWhere('code', 'TX');
        $this->assertNotNull($stateData);
        $this->assertArrayNotHasKey('jobs_count', $stateData);
        $this->assertEquals(1, $stateData['total_resources']);
    }
}
