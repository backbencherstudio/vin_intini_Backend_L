<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($user, 'api')->getJson('/api/groups');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(10, 'data');

        $returnedGroups = collect($response->json('data'));

        $this->assertFalse($returnedGroups->contains(fn(array $group) => (int) $group['id'] === $joinedGroup->id));

        $returnedGroups->each(function (array $group) use ($eligibleGroups): void {
            $this->assertSame('public', $group['type']);
            $this->assertSame('listed', $group['discoverability']);
            $this->assertTrue($eligibleGroups->contains('id', (int) $group['id']));
        });
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
