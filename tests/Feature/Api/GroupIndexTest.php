<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GroupIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_gets_random_ten_public_groups_and_joined_groups_are_hidden(): void
    {
        $user = $this->makeUser();
        $creator = User::factory()->create();

        $eligibleGroups = collect();

        for ($index = 1; $index <= 15; $index++) {
            $eligibleGroups->push(Group::create([
                'name' => 'Public Listed Group ' . $index,
                'description' => 'Group description ' . $index,
                'industry' => ['Technology'],
                'creator_id' => $creator->id,
                'type' => 'public',
                'discoverability' => 'listed',
            ]));
        }

        $joinedGroup = Group::create([
            'name' => 'Joined Group',
            'description' => 'Already joined by auth user',
            'industry' => ['Technology'],
            'creator_id' => $creator->id,
            'type' => 'public',
            'discoverability' => 'listed',
        ]);

        $joinedGroup->members()->attach($user->id, ['role' => 'member']);

        Group::create([
            'name' => 'Private Group',
            'description' => 'Should not be returned',
            'industry' => ['Technology'],
            'creator_id' => $creator->id,
            'type' => 'private',
            'discoverability' => 'listed',
        ]);

        Group::create([
            'name' => 'Unlisted Group',
            'description' => 'Should not be returned',
            'industry' => ['Technology'],
            'creator_id' => $creator->id,
            'type' => 'public',
            'discoverability' => 'unlisted',
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/groups-suggestions');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group suggestions retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('total', 10)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1);

        $returnedGroups = collect($response->json('data'));

        $this->assertFalse($returnedGroups->contains(fn(array $group) => (int) $group['id'] === $joinedGroup->id));

        $returnedGroups->each(function (array $group) use ($eligibleGroups): void {
            $this->assertArrayHasKey('name', $group);
            $this->assertArrayHasKey('logo_url', $group);
            $this->assertArrayHasKey('total_member', $group);
            $this->assertIsInt($group['total_member']);
            $this->assertTrue($eligibleGroups->contains('id', (int) $group['id']));
        });
    }

    public function test_user_gets_custom_error_when_group_is_missing(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'api')->getJson('/api/group-show/999999');

        $response
            ->assertStatus(404)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Group not found');
    }

    public function test_group_creator_can_update_group_images(): void
    {
        Storage::fake('public');

        $creator = $this->makeUser();
        $group = Group::create([
            'name' => 'Design Guild',
            'description' => 'A group for designers',
            'industry' => ['Design'],
            'creator_id' => $creator->id,
            'type' => 'public',
            'discoverability' => 'listed',
            'logo' => 'group_logos/old-logo.png',
            'cover_photo' => 'group_covers/old-cover.png',
        ]);

        Storage::disk('public')->put('group_logos/old-logo.png', 'old-logo');
        Storage::disk('public')->put('group_covers/old-cover.png', 'old-cover');

        $response = $this->actingAs($creator, 'api')->postJson('/api/group-images/' . $group->id, [
            'logo' => UploadedFile::fake()->image('logo.png'),
            'cover_photo' => UploadedFile::fake()->image('cover.png'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Group images updated successfully!')
            ->assertJsonPath('data.logo', fn(?string $value) => is_string($value) && str_starts_with($value, 'group_logos/'))
            ->assertJsonPath('data.cover_photo', fn(?string $value) => is_string($value) && str_starts_with($value, 'group_covers/'));

        $group->refresh();

        $this->assertNotSame('group_logos/old-logo.png', $group->logo);
        $this->assertNotSame('group_covers/old-cover.png', $group->cover_photo);
        Storage::disk('public')->assertMissing('group_logos/old-logo.png');
        Storage::disk('public')->assertMissing('group_covers/old-cover.png');
    }

    private function makeUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'title' => fake()->jobTitle(),
        ]);

        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
