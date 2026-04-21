<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification
{
    public function __construct(
        public Group $group,
        public User $inviter,
        public string $inviteLink
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $inviterName = trim(($this->inviter->first_name ?? '') . ' ' . ($this->inviter->last_name ?? ''));

        return [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_logo_url' => $this->group->logo_url,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $inviterName,
            'message' => $inviterName . ' invited you to join ' . $this->group->name,
            'invite_link' => $this->inviteLink,
            'type' => 'group_invitation',
            'sent_at' => now()->toDateTimeString(),
        ];
    }
}
