<?php

namespace Tests\Feature\Api;

use App\Models\ConnectionRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GroupInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $this->creator = User::factory()->create([
            'is_verified' => true,
        ]);
        $this->creator->assignRole($role);

        UserProfile::create([
            'user_id' => $this->creator->id,
            'country' => 'Bangladesh',
        ]);
    }

    public function test_creator_can_list_connected_users_who_are_not_in_the_group(): void
    {
        $inviteeOne = $this->makeConnectedUser();
        $inviteeTwo = $this->makeConnectedUser();
        $groupMember = $this->makeConnectedUser();
        $nonConnectedUser = $this->makeUser();

        $group = $this->createGroup();

        $this->acceptConnection($this->creator, $inviteeOne);
        $this->acceptConnection($this->creator, $inviteeTwo);
        $this->acceptConnection($this->creator, $groupMember);
        $group->members()->attach($groupMember->id, ['role' => 'member']);

        $response = $this->actingAs($this->creator, 'api')
            ->getJson('/api/group-invite-users/' . $group->id);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $returnedIds = collect($response->json('data'))->pluck('id')->map(fn($id) => (int) $id)->all();

        $this->assertContains($inviteeOne->id, $returnedIds);
        $this->assertContains($inviteeTwo->id, $returnedIds);
        $this->assertNotContains($groupMember->id, $returnedIds);
        $this->assertNotContains($nonConnectedUser->id, $returnedIds);
    }

    public function test_creator_can_send_group_invitations_to_connected_users(): void
    {
        Notification::fake();

        $inviteeOne = $this->makeConnectedUser();
        $inviteeTwo = $this->makeConnectedUser();
        $group = $this->createGroup();

        $this->acceptConnection($this->creator, $inviteeOne);
        $this->acceptConnection($this->creator, $inviteeTwo);

        $response = $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$inviteeOne->id, $inviteeTwo->id],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Group invitations sent successfully.')
            ->assertJsonPath('data.group.id', $group->id)
            ->assertJsonCount(2, 'data.invited_users');

        Notification::assertSentTo($inviteeOne, GroupInvitationNotification::class, function (GroupInvitationNotification $notification, array $channels) use ($group): bool {
            $this->assertSame(['database'], $channels);
            $this->assertSame($group->id, $notification->group->id);
            $this->assertSame($group->name, $notification->group->name);

            return true;
        });

        Notification::assertSentTo($inviteeTwo, GroupInvitationNotification::class, function (GroupInvitationNotification $notification, array $channels) use ($group): bool {
            $this->assertSame(['database'], $channels);
            $this->assertSame($group->id, $notification->group->id);
            $this->assertSame($group->name, $notification->group->name);

            return true;
        });
    }

    public function test_invitations_reject_users_who_are_not_connected_or_already_members(): void
    {
        $invitee = $this->makeConnectedUser();
        $alreadyMember = $this->makeConnectedUser();
        $unrelatedUser = $this->makeUser();
        $group = $this->createGroup();

        $this->acceptConnection($this->creator, $invitee);
        $this->acceptConnection($this->creator, $alreadyMember);
        $group->members()->attach($alreadyMember->id, ['role' => 'member']);

        $response = $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id, $alreadyMember->id, $unrelatedUser->id],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Some selected users are not eligible for invitation.');

        $invalidIds = collect($response->json('data.invalid_user_ids'))->map(fn($id) => (int) $id)->all();

        $this->assertContains($alreadyMember->id, $invalidIds);
        $this->assertContains($unrelatedUser->id, $invalidIds);
        $this->assertNotContains($invitee->id, $invalidIds);
    }

    public function test_public_group_member_can_list_and_send_invitations_to_connected_users(): void
    {
        Notification::fake();

        $groupMember = $this->makeUser();
        $invitee = $this->makeConnectedUser();
        $group = $this->createGroup('public');

        $group->members()->attach($groupMember->id, ['role' => 'member']);
        $this->acceptConnection($groupMember, $invitee);

        $listResponse = $this->actingAs($groupMember, 'api')
            ->getJson('/api/group-invite-users/' . $group->id);

        $listResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');

        $sendResponse = $this->actingAs($groupMember, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ]);

        $sendResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.invited_users.0.id', $invitee->id);

        Notification::assertSentTo($invitee, GroupInvitationNotification::class);
    }

    public function test_private_group_non_creator_cannot_invite_users(): void
    {
        $member = $this->makeUser();
        $invitee = $this->makeConnectedUser();
        $group = $this->createGroup('private');

        $group->members()->attach($member->id, ['role' => 'member']);
        $this->acceptConnection($member, $invitee);

        $listResponse = $this->actingAs($member, 'api')
            ->getJson('/api/group-invite-users/' . $group->id);

        $listResponse
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'You are not allowed to invite users to this group.');

        $sendResponse = $this->actingAs($member, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ]);

        $sendResponse
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'You are not allowed to invite users to this group.');
    }

    private function createGroup(string $type = 'public'): Group
    {
        $group = Group::create([
            'name' => 'Research Circle',
            'description' => 'A group for invited connections',
            'industry' => ['Technology'],
            'creator_id' => $this->creator->id,
            'type' => $type,
            'discoverability' => 'listed',
            'allow_member_invites' => true,
        ]);

        $group->members()->attach($this->creator->id, ['role' => 'admin']);

        return $group;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create([
            'is_verified' => true,
        ]);

        $user->assignRole(Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]));

        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        return $user;
    }

    private function makeConnectedUser(): User
    {
        return $this->makeUser();
    }

    private function acceptConnection(User $firstUser, User $secondUser): void
    {
        ConnectionRequest::create([
            'sender_id' => $firstUser->id,
            'receiver_id' => $secondUser->id,
            'status' => ConnectionRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }
}
