<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserExperienceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_default_company_suggestions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        Company::factory()->create(['name' => 'Acme Corp']);
        Company::factory()->create(['name' => 'Bongo Labs']);
        Company::factory()->create(['name' => 'Zen Tech']);

        $response = $this->actingAs($user, 'api')->getJson('/api/experience/company-suggestions');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Acme Corp')
            ->assertJsonPath('data.1.name', 'Bongo Labs')
            ->assertJsonPath('data.2.name', 'Zen Tech');
    }

    public function test_user_can_search_company_suggestions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        Company::factory()->create(['name' => 'Softvence Delta']);
        Company::factory()->create(['name' => 'Softvence Studio']);
        Company::factory()->create(['name' => 'Beta Works']);

        $response = $this->actingAs($user, 'api')->getJson('/api/experience/company-suggestions?search=softv');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Softvence Delta')
            ->assertJsonPath('data.1.name', 'Softvence Studio');
    }

    public function test_user_can_get_default_and_search_skill_suggestions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        Skill::create(['name' => 'JavaScript']);
        Skill::create(['name' => 'Laravel']);
        Skill::create(['name' => 'PHP']);

        $defaultResponse = $this->actingAs($user, 'api')->getJson('/api/experience/skill-suggestions');

        $defaultResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'JavaScript')
            ->assertJsonPath('data.1.name', 'Laravel')
            ->assertJsonPath('data.2.name', 'PHP');

        $searchResponse = $this->actingAs($user, 'api')->getJson('/api/experience/skill-suggestions?search=lar');

        $searchResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laravel');
    }

    public function test_user_can_get_single_experience_for_edit(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        $company = Company::factory()->create(['name' => 'Softvence Delta']);

        $experience = Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->subMonths(1)->startOfMonth(),
            'is_current' => false,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/experience/edit/'.$experience->id);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $experience->id)
            ->assertJsonPath('data.company_name', 'Softvence Delta');
    }

    public function test_user_can_update_own_experience(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        $experience = Experience::factory()->create([
            'user_id' => $user->id,
            'is_current' => true,
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/experience/update/'.$experience->id, [
            'title' => 'Senior UX Designer',
            'company_name' => 'Softvence Agency',
            'employment_type' => 'Full-time',
            'location' => 'Dhaka',
            'location_type' => 'Remote',
            'start_month' => 'March',
            'start_year' => '2023',
            'end_month' => 'March',
            'end_year' => '2024',
            'is_current' => false,
            'description' => 'Updated description',
            'skills' => ['Figma', 'Prototyping'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Experience updated successfully')
            ->assertJsonPath('data.title', 'Senior UX Designer')
            ->assertJsonPath('data.company.name', 'Softvence Agency')
            ->assertJsonPath('data.is_current', false);

        $this->assertDatabaseHas('experiences', [
            'id' => $experience->id,
            'title' => 'Senior UX Designer',
            'is_current' => 0,
        ]);
    }

    public function test_user_can_delete_own_experience(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        $experience = Experience::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')->deleteJson('/api/experience/delete/'.$experience->id);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Experience deleted successfully');

        $this->assertDatabaseMissing('experiences', [
            'id' => $experience->id,
        ]);
    }
}
