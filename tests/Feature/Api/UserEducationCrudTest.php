<?php

namespace Tests\Feature\Api;

use App\Models\Education;
use App\Models\Institution;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserEducationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_institution_suggestions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $institution = Institution::create([
            'name' => 'ABC University',
            'type' => 'University',
            'country' => 'Bangladesh',
            'website' => 'https://abc.edu',
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/institution-suggestions?search=ABC');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id', $institution->id)
            ->assertJsonPath('data.0.name', 'ABC University');
    }

    public function test_user_can_add_education_and_create_institution_when_missing(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $skill = Skill::create(['name' => 'Laravel']);

        $response = $this->actingAs($user, 'api')->postJson('/api/education/add', [
            'institution' => 'New Horizon University',
            'degree' => 'BSc',
            'field_study' => 'Computer Science',
            'start_month' => 'January',
            'start_year' => '2020',
            'end_month' => 'December',
            'end_year' => '2024',
            'grade' => '3.70',
            'description' => 'Completed undergraduate studies.',
            'activities' => 'Programming Club, Hackathon',
            'skills' => ['Laravel'],
            'is_current' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.degree', 'BSc')
            ->assertJsonPath('data.status', 'Complete')
            ->assertJsonPath('data.skills_id.0', $skill->id);

        $institution = Institution::query()->where('name', 'New Horizon University')->first();
        $this->assertNotNull($institution);

        $education = Education::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($education);
        $this->assertSame($institution->id, $education->institution_id);
        $this->assertSame([$skill->id], $education->skills_id);
        $this->assertSame('Programming Club, Hackathon', $education->activities);

        $listResponse = $this->actingAs($user, 'api')->getJson('/api/education/list');

        $listResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.institution.name', 'New Horizon University')
            ->assertJsonPath('data.0.status', 'Complete');
    }

    public function test_is_current_true_sets_end_fields_to_null_and_status_present(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/education/add', [
            'institution' => 'Current Institute',
            'degree' => 'MSc',
            'field_study' => 'Software Engineering',
            'start_month' => 'January',
            'start_year' => '2025',
            'end_month' => 'December',
            'end_year' => '2026',
            'is_current' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.end_month', null)
            ->assertJsonPath('data.end_year', null)
            ->assertJsonPath('data.status', 'Present');

        $education = Education::query()->where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($education);
        $this->assertNull($education->end_month);
        $this->assertNull($education->end_year);
        $this->assertSame('Present', $education->status);
    }
}
