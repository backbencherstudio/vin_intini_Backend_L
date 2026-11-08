<?php

namespace App\Services\Group;

use App\Models\Connection;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationAcceptedNotification;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Support\Collection;

class GroupMembershipService
{
    /**
     * Determine if a user is permitted to send invitations for a group.
     */
    public function canInviteToGroup(Group $group, int $userId): bool
    {
        if ((int) $group->creator_id === $userId) {
            return true;
        }

        if ($group->type === 'private') {
            return false;
        }

        return (bool) $group->allow_member_invites
            || $group->members()->where('user_id', $userId)->exists();
    }

    /**
     * Get pending invitation user IDs for a group.
     *
     * @return Collection<int, int>
     */
    public function pendingInvitationUserIds(int $groupId): Collection
    {
        return GroupInvitation::query()
            ->where('group_id', $groupId)
            ->pluck('invited_user_id')
            ->map(fn ($userId): int => (int) $userId)
            ->values();
    }

    /**
     * Get IDs of users connected with the given user.
     *
     * @return Collection<int, int>
     */
    public function connectedUserIds(int $userId): Collection
    {
        return Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userId): void {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->get(['sender_id', 'receiver_id'])
            ->map(fn ($conn): int => (int) ($conn->sender_id === $userId ? $conn->receiver_id : $conn->sender_id))
            ->unique()
            ->values();
    }

    /**
     * Send group invitations to verified connected users.
     *
     * @param  array<int, int>  $requestedUserIds
     * @return array{
     *     success: bool,
     *     invalid_user_ids?: array<int, int>,
     *     invited_users?: array<int, array<string, mixed>>
     * }
     */
    public function sendInvitations(User $currentUser, Group $group, array $requestedUserIds): array
    {
        $requested = collect($requestedUserIds)->map(fn ($id): int => (int) $id)->unique()->values();

        $eligibleUserIds = $this->connectedUserIds($currentUser->id)
            ->diff($group->members()->pluck('users.id')->map(fn ($userId): int => (int) $userId))
            ->diff($this->pendingInvitationUserIds($group->id));

        $invalidUserIds = $requested->diff($eligibleUserIds)->values();

        if ($invalidUserIds->isNotEmpty()) {
            return [
                'success' => false,
                'invalid_user_ids' => $invalidUserIds->all(),
            ];
        }

        $invitedUsers = [];
        foreach ($requested as $userId) {
            $invitee = User::find($userId);
            if (! $invitee) {
                continue;
            }

            $invitation = GroupInvitation::create([
                'group_id' => $group->id,
                'inviter_id' => $currentUser->id,
                'invited_user_id' => $invitee->id,
            ]);

            $invitee->notify(new GroupInvitationNotification($invitation, $group, $currentUser));

            $invitedUsers[] = [
                'invitation_id' => $invitation->id,
                'id' => $invitee->id,
                'username' => $invitee->username,
                'first_name' => $invitee->first_name,
                'last_name' => $invitee->last_name,
                'title' => $invitee->title,
                'profile_image_url' => $invitee->profile_image_url,
            ];
        }

        return [
            'success' => true,
            'invited_users' => $invitedUsers,
        ];
    }

    /**
     * Accept a pending invitation, attach member, and notify inviter.
     *
     * @return array<string, mixed>|null
     */
    public function acceptInvitation(User $acceptor, int $invitationId): ?array
    {
        $invitation = GroupInvitation::query()
            ->with(['group', 'inviter'])
            ->where('id', $invitationId)
            ->where('invited_user_id', $acceptor->id)
            ->first();

        if (! $invitation || ! $invitation->group) {
            return null;
        }

        $group = $invitation->group;
        $inviter = $invitation->inviter;
        $currentUserId = $acceptor->id;

        $isMember = $group->members()->where('user_id', $currentUserId)->exists();

        if (! $isMember) {
            $group->members()->attach($currentUserId, ['role' => 'member']);
        }

        $invitation->delete();
        $this->deleteGroupInvitationNotification($acceptor, $group->id);

        if ($inviter && (int) $inviter->id !== (int) $acceptor->id) {
            $inviter->notify(new GroupInvitationAcceptedNotification($group, $acceptor, $inviter));
        }

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
            ],
            'is_new_member' => ! $isMember,
        ];
    }

    /**
     * Ignore/dismiss a pending invitation.
     */
    public function ignoreInvitation(User $user, int $invitationId): ?Group
    {
        $invitation = GroupInvitation::query()
            ->with('group')
            ->where('id', $invitationId)
            ->where('invited_user_id', $user->id)
            ->first();

        if (! $invitation || ! $invitation->group) {
            return null;
        }

        $group = $invitation->group;
        $invitation->delete();

        $this->deleteGroupInvitationNotification($user, $group->id);

        return $group;
    }

    /**
     * Delete stored group invitation notification records.
     */
    public function deleteGroupInvitationNotification(User $user, int $groupId): void
    {
        $user->notifications()
            ->where('type', GroupInvitationNotification::class)
            ->where('data->group_id', $groupId)
            ->delete();
    }
}
