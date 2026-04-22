<?php

namespace Tests\Feature\Api;

use App\Models\ConnectionRequest;
use App\Models\Group;
use App\Models\GroupInvitation;
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
            ->assertJsonPath('total', 2)
            ->assertJsonPath('limit', 15)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1);

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

        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $group->id,
            'inviter_id' => $this->creator->id,
            'invited_user_id' => $inviteeOne->id,
        ]);

        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $group->id,
            'inviter_id' => $this->creator->id,
            'invited_user_id' => $inviteeTwo->id,
        ]);

        Notification::assertSentTo($inviteeOne, GroupInvitationNotification::class, function (GroupInvitationNotification $notification, array $channels) use ($group): bool {
            $this->assertSame(['database'], $channels);
            $this->assertSame($group->id, $notification->group->id);
            $this->assertSame($group->name, $notification->group->name);
            $this->assertNotNull($notification->invitation->id);

            return true;
        });

        Notification::assertSentTo($inviteeTwo, GroupInvitationNotification::class, function (GroupInvitationNotification $notification, array $channels) use ($group): bool {
            $this->assertSame(['database'], $channels);
            $this->assertSame($group->id, $notification->group->id);
            $this->assertSame($group->name, $notification->group->name);
            $this->assertNotNull($notification->invitation->id);

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

    public function test_invited_user_can_accept_invitation_and_join_group(): void
    {
        Notification::fake();

        $invitee = $this->makeConnectedUser();
        $group = $this->createGroup('private');

        $this->acceptConnection($this->creator, $invitee);

        $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ])
            ->assertOk();

        $invitation = GroupInvitation::query()
            ->where('group_id', $group->id)
            ->where('invited_user_id', $invitee->id)
            ->firstOrFail();

        $response = $this->actingAs($invitee, 'api')
            ->postJson('/api/group-invitations/' . $invitation->id . '/accept');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Invitation accepted successfully.')
            ->assertJsonPath('data.group_id', $group->id);

        $this->assertDatabaseMissing('group_invitations', [
            'id' => $invitation->id,
        ]);

        $this->assertTrue($group->fresh()->members()->where('user_id', $invitee->id)->exists());
    }

    public function test_invited_user_can_ignore_invitation_and_sender_can_reinvite(): void
    {
        Notification::fake();

        $invitee = $this->makeConnectedUser();
        $group = $this->createGroup();

        $this->acceptConnection($this->creator, $invitee);

        $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ])
            ->assertOk();

        $invitation = GroupInvitation::query()
            ->where('group_id', $group->id)
            ->where('invited_user_id', $invitee->id)
            ->firstOrFail();

        $ignoreResponse = $this->actingAs($invitee, 'api')
            ->postJson('/api/group-invitations/' . $invitation->id . '/ignore');

        $ignoreResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Invitation ignored successfully.');

        $this->assertDatabaseMissing('group_invitations', [
            'id' => $invitation->id,
        ]);

        $resendResponse = $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ]);

        $resendResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.invited_users');

        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $group->id,
            'invited_user_id' => $invitee->id,
        ]);
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

    public function test_invited_user_can_view_only_group_invitation_requests(): void
    {
        $invitee = $this->makeConnectedUser();
        $group = $this->createGroup('public');

        $this->acceptConnection($this->creator, $invitee);

        $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $group->id, [
                'user_ids' => [$invitee->id],
            ])
            ->assertOk();

        $response = $this->actingAs($invitee, 'api')
            ->getJson('/api/group-invitations/requests');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group invitations retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonPath('data.0.group.id', $group->id)
            ->assertJsonPath('data.0.group.name', $group->name)
            ->assertJsonPath('data.0.inviter.id', $this->creator->id)
            ->assertJsonPath('filters.search', null);
    }

    public function test_invited_user_can_search_and_paginate_group_invitation_requests(): void
    {
        $invitee = $this->makeConnectedUser();

        $groupOne = Group::create([
            'name' => 'Research Circle A',
            'description' => 'A group for invited connections',
            'industry' => ['Technology'],
            'creator_id' => $this->creator->id,
            'type' => 'public',
            'discoverability' => 'listed',
            'allow_member_invites' => true,
        ]);
        $groupOne->members()->attach($this->creator->id, ['role' => 'admin']);

        $groupTwo = Group::create([
            'name' => 'Marketing Hub',
            'description' => 'A group for invited connections',
            'industry' => ['Technology'],
            'creator_id' => $this->creator->id,
            'type' => 'public',
            'discoverability' => 'listed',
            'allow_member_invites' => true,
        ]);
        $groupTwo->members()->attach($this->creator->id, ['role' => 'admin']);

        $this->acceptConnection($this->creator, $invitee);

        $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $groupOne->id, [
                'user_ids' => [$invitee->id],
            ])
            ->assertOk();

        $this->actingAs($this->creator, 'api')
            ->postJson('/api/group-invite-users/' . $groupTwo->id, [
                'user_ids' => [$invitee->id],
            ])
            ->assertOk();

        $searchResponse = $this->actingAs($invitee, 'api')
            ->getJson('/api/group-invitations/requests?search=research');

        $searchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group invitations retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.group.id', $groupOne->id)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonPath('filters.search', 'research');

        $paginationResponse = $this->actingAs($invitee, 'api')
            ->getJson('/api/group-invitations/requests?per_page=1&page=2');

        $paginationResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group invitations retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('total', 2)
            ->assertJsonPath('limit', 1)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('total_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('filters.search', null);
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
