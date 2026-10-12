<?php

namespace Tests\Feature\Api;

use App\Models\Connection;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\GroupInvitationAcceptedNotification;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GroupInvitationNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $inviter;

    private User $invitee;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $this->inviter = User::factory()->create([
            'is_verified' => true,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
        ]);
        $this->inviter->assignRole($role);
        UserProfile::create(['user_id' => $this->inviter->id, 'country' => 'Bangladesh']);

        $this->invitee = User::factory()->create([
            'is_verified' => true,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'username' => 'janesmith',
        ]);
        $this->invitee->assignRole($role);
        UserProfile::create(['user_id' => $this->invitee->id, 'country' => 'Bangladesh']);

        Connection::create([
            'sender_id' => $this->inviter->id,
            'receiver_id' => $this->invitee->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $this->group = Group::create([
            'name' => 'Laravel Artisans',
            'slug' => 'laravel-artisans',
            'description' => 'A group for artisan devs',
            'industry' => ['Technology'],
            'creator_id' => $this->inviter->id,
            'type' => 'public',
            'discoverability' => 'listed',
            'allow_member_invites' => true,
        ]);
        $this->group->members()->attach($this->inviter->id, ['role' => 'admin']);
    }

    public function test_group_invitation_broadcasts_to_correct_invitee_private_channel(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        Notification::fake();

        $response = $this->actingAs($this->inviter, 'api')
            ->postJson('/api/group-invite-user/'.$this->group->id, [
                'user_id' => $this->invitee->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Group invitation sent successfully.');

        $invitation = GroupInvitation::where('group_id', $this->group->id)
            ->where('invited_user_id', $this->invitee->id)
            ->firstOrFail();

        Notification::assertSentTo($this->invitee, GroupInvitationNotification::class, function (GroupInvitationNotification $notification): bool {
            $channels = $notification->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertSame('private-App.Models.User.'.$this->invitee->id, $channels[0]->name);

            $data = $notification->toArray($this->invitee);
            $this->assertSame($this->inviter->id, $data['sender_id']);
            $this->assertSame('johndoe', $data['sender_username']);
            $this->assertSame('John Doe', $data['sender_name']);
            $this->assertSame($this->group->id, $data['group_id']);
            $this->assertSame('laravel-artisans', $data['group_slug']);
            $this->assertSame('Laravel Artisans', $data['group_name']);
            $this->assertSame('invited you to join Laravel Artisans', $data['message']);

            return true;
        });
    }

    public function test_accepting_group_invitation_notifies_inviter_realtime_and_database(): void
    {
        Notification::fake();

        $invitation = GroupInvitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->inviter->id,
            'invited_user_id' => $this->invitee->id,
        ]);

        $this->invitee->notify(new GroupInvitationNotification($invitation, $this->group, $this->inviter));

        $response = $this->actingAs($this->invitee, 'api')
            ->postJson('/api/group-invitations/'.$invitation->id.'/accept');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Invitation accepted successfully.');

        // Inviter is notified
        Notification::assertSentTo($this->inviter, GroupInvitationAcceptedNotification::class, function (GroupInvitationAcceptedNotification $notification): bool {
            $channels = $notification->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertSame('private-App.Models.User.'.$this->inviter->id, $channels[0]->name);

            $data = $notification->toArray($this->inviter);
            $this->assertSame($this->invitee->id, $data['sender_id']);
            $this->assertSame('janesmith', $data['sender_username']);
            $this->assertSame('Jane Smith', $data['sender_name']);
            $this->assertSame($this->group->id, $data['group_id']);
            $this->assertSame('laravel-artisans', $data['group_slug']);
            $this->assertSame('Laravel Artisans', $data['group_name']);
            $this->assertSame('accepted your invitation to join Laravel Artisans', $data['message']);

            return true;
        });
    }

    public function test_inviter_can_fetch_group_invitation_accepted_notification_via_api(): void
    {
        // Actually save to database without mocking
        $this->inviter->notify(new GroupInvitationAcceptedNotification($this->group, $this->invitee, $this->inviter));

        $response = $this->actingAs($this->inviter, 'api')->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.total_notifications', 1)
            ->assertJsonPath('data.0.type', 'GroupInvitationAcceptedNotification')
            ->assertJsonPath('data.0.data.sender_id', $this->invitee->id)
            ->assertJsonPath('data.0.data.sender_username', 'janesmith')
            ->assertJsonPath('data.0.data.sender_name', 'Jane Smith')
            ->assertJsonPath('data.0.data.group_id', $this->group->id)
            ->assertJsonPath('data.0.data.group_name', 'Laravel Artisans')
            ->assertJsonPath('data.0.data.message', 'accepted your invitation to join Laravel Artisans');

        // Check that redundant keys are unset
        $itemData = $response->json('data.0.data');
        $this->assertArrayNotHasKey('unread_count', $itemData);
        $this->assertArrayNotHasKey('avatar', $itemData);
        $this->assertArrayNotHasKey('image', $itemData);
        $this->assertArrayNotHasKey('industry_logo', $itemData);
    }

    public function test_joining_group_with_pending_invitation_notifies_inviter_and_cleans_up(): void
    {
        Notification::fake();

        $invitation = GroupInvitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->inviter->id,
            'invited_user_id' => $this->invitee->id,
        ]);

        $response = $this->actingAs($this->invitee, 'api')
            ->postJson('/api/group/join', [
                'group_id' => $this->group->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('group_invitations', [
            'id' => $invitation->id,
        ]);

        Notification::assertSentTo($this->inviter, GroupInvitationAcceptedNotification::class);
    }
}
