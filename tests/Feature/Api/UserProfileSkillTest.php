<?php

namespace Tests\Feature\Api;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserProfileSkillTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_show_returns_requested_profile_fields(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
            'first_name' => 'Niaz',
            'last_name' => 'Ahmed',
            'title' => 'Designer',
        ]);
        $user->assignRole($role);

        $skillLaravel = Skill::create(['name' => 'Laravel']);
        $skillPhp = Skill::create(['name' => 'PHP']);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
            'skills_id' => [$skillLaravel->id, $skillPhp->id],
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.first_name', 'Niaz')
            ->assertJsonPath('data.last_name', 'Ahmed')
            ->assertJsonPath('data.title', 'Designer')
            ->assertJsonPath('data.country', 'Bangladesh')
            ->assertJsonCount(2, 'data.skills')
            ->assertJsonPath('data.skills.0.name', 'Laravel')
            ->assertJsonPath('data.skills.1.name', 'PHP');
    }

    public function test_setup_profile_stores_skill_ids_from_skills_table(): void
    {
        $user = User::factory()->create([
            'is_verified' => true,
        ]);

        $existingSkill = Skill::create(['name' => 'Laravel']);

        $response = $this->actingAs($user, 'api')->postJson('/api/setup-profile', [
            'first_name' => 'Niaz',
            'last_name' => 'Ahmed',
            'title' => 'Designer',
            'country' => 'Bangladesh',
            'profession' => 'UI, UX',
            'interests' => 'Design, Research',
            'study_category' => 'Computer Science',
            'study_subcategory' => 'Software',
            'postal_code' => '1207',
            'highest_degree' => 'BSc',
            'institution' => 'ABC University',
            'graduation_year' => '2024',
            'about' => 'Profile setup test',
            'skills' => ['Laravel', 'Figma'],
        ]);

        $newSkillId = Skill::query()->where('name', 'Figma')->value('id');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.profile.skills_id.0', $existingSkill->id)
            ->assertJsonPath('data.profile.skills_id.1', $newSkillId);

        $profile = UserProfile::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($profile);
        $this->assertSame([$existingSkill->id, $newSkillId], $profile->skills_id);

        $meResponse = $this->actingAs($user, 'api')->getJson('/api/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.profile.skills.0', 'Figma')
            ->assertJsonPath('user.profile.skills.1', 'Laravel');
    }

    public function test_verified_user_can_get_profile_skill_suggestions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        Skill::create(['name' => 'JavaScript']);
        Skill::create(['name' => 'Laravel']);
        Skill::create(['name' => 'PHP']);

        $defaultResponse = $this->actingAs($user, 'api')->getJson('/api/skill-suggestions');

        $defaultResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'JavaScript')
            ->assertJsonPath('data.1.name', 'Laravel')
            ->assertJsonPath('data.2.name', 'PHP');

        $searchResponse = $this->actingAs($user, 'api')->getJson('/api/skill-suggestions?search=lar');

        $searchResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laravel');
    }
}
