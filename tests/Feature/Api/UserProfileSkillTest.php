<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Institution;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $company = Company::factory()->create(['name' => 'Softvence Delta']);
        $currentPosition = Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'title' => 'Senior Designer',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
            'skills_id' => [$skillLaravel->id, $skillPhp->id],
            'current_position_id' => $currentPosition->id,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.first_name', 'Niaz')
            ->assertJsonPath('data.last_name', 'Ahmed')
            ->assertJsonPath('data.title', 'Designer')
            ->assertJsonPath('data.country', 'Bangladesh')
            ->assertJsonPath('data.current_position_id', $currentPosition->id)
            ->assertJsonPath('data.current_position.title', 'Senior Designer')
            ->assertJsonPath('data.current_position.company_name', 'Softvence Delta')
            ->assertJsonCount(2, 'data.skills')
            ->assertJsonPath('data.skills.0.name', 'Laravel')
            ->assertJsonPath('data.skills.1.name', 'PHP');
    }

    public function test_user_can_update_current_position_in_profile(): void
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

        $company = Company::factory()->create(['name' => 'Acme Corp']);
        $experience = Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'title' => 'Lead Engineer',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $response = $this->actingAs($user, 'api')->putJson('/api/profile/update', [
            'current_position_id' => $experience->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.profile.current_position_id', $experience->id);

        $showResponse = $this->actingAs($user, 'api')->getJson('/api/profile');

        $showResponse
            ->assertOk()
            ->assertJsonPath('data.current_position_id', $experience->id)
            ->assertJsonPath('data.current_position.title', 'Lead Engineer')
            ->assertJsonPath('data.current_position.company_name', 'Acme Corp');
    }

    public function test_user_can_update_current_institute_in_profile(): void
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

        $institution = Institution::create([
            'name' => 'Daffodil International University',
            'type' => 'University',
            'country' => 'Bangladesh',
            'website' => 'https://daffodilvarsity.edu.bd',
        ]);

        Education::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'degree' => 'BSc',
            'field_study' => 'Computer Science',
            'start_month' => 'January',
            'start_year' => '2022',
            'end_month' => null,
            'end_year' => null,
            'grade' => null,
            'description' => null,
            'activities' => null,
            'is_current' => true,
            'skills_id' => [],
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $response = $this->actingAs($user, 'api')->putJson('/api/profile/update', [
            'current_institute_id' => $institution->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.profile.current_institute_id', $institution->id);

        $showResponse = $this->actingAs($user, 'api')->getJson('/api/profile');

        $showResponse
            ->assertOk()
            ->assertJsonPath('data.current_institute_id', $institution->id)
            ->assertJsonPath('data.current_institute.name', 'Daffodil International University');
    }

    public function test_user_can_update_profile_with_up_to_five_skills(): void
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

        $response = $this->actingAs($user, 'api')->putJson('/api/profile/update', [
            'skills' => ['Laravel', 'PHP', 'Vue', 'MySQL', 'Docker'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(5, 'data.profile.skills_id');
    }

    public function test_user_cannot_update_profile_with_more_than_five_skills(): void
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

        $response = $this->actingAs($user, 'api')->putJson('/api/profile/update', [
            'skills' => ['Laravel', 'PHP', 'Vue', 'MySQL', 'Docker', 'Redis'],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['skills']);
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

        $profile->update([
            'highest_degree' => 'MBA',
            'study_subcategory' => 'Business',
            'institution' => 'Different Institute',
            'graduation_year' => '2030',
        ]);

        $institution = Institution::query()->where('name', 'ABC University')->first();

        $this->assertNotNull($institution);
        $this->assertDatabaseHas('educations', [
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'degree' => 'BSc',
            'field_study' => 'Software',
            'end_year' => '2024',
        ]);

        $meResponse = $this->actingAs($user, 'api')->getJson('/api/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.profile.highest_degree', 'BSc')
            ->assertJsonPath('user.profile.study_subcategory', 'Software')
            ->assertJsonPath('user.profile.institution', 'ABC University')
            ->assertJsonPath('user.profile.graduation_year', '2024')
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

    public function test_user_can_update_profile_and_cover_images(): void
    {
        Storage::fake('public');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
            'profile_image' => 'profile_photos/old-profile.jpg',
            'cover_image' => 'cover_photos/old-cover.jpg',
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        Storage::disk('public')->put('profile_photos/old-profile.jpg', 'old-image');
        Storage::disk('public')->put('cover_photos/old-cover.jpg', 'old-image');

        $response = $this->actingAs($user, 'api')->post('/api/profile/images', [
            'profile_image' => UploadedFile::fake()->image('profile.jpg', 600, 600),
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1280, 720),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Profile images updated successfully!');

        $user->refresh();

        $this->assertNotNull($user->profile_image);
        $this->assertNotNull($user->cover_image);
        $this->assertStringStartsWith('profile_photos/', $user->profile_image);
        $this->assertStringStartsWith('cover_photos/', $user->cover_image);
        $this->assertNotSame('profile_photos/old-profile.jpg', $user->profile_image);
        $this->assertNotSame('cover_photos/old-cover.jpg', $user->cover_image);

        $this->assertFalse(Storage::disk('public')->exists('profile_photos/old-profile.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('cover_photos/old-cover.jpg'));
        $this->assertTrue(Storage::disk('public')->exists($user->profile_image));
        $this->assertTrue(Storage::disk('public')->exists($user->cover_image));
    }

    public function test_user_cannot_update_images_without_any_image_file(): void
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

        $response = $this->actingAs($user, 'api')->postJson('/api/profile/images', []);

        $response
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'At least one image file is required.');
    }
}
