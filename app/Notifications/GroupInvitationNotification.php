<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public GroupInvitation $invitation,
        public Group $group,
        public User $inviter
    ) {}


    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->hasValidPusherBroadcastConfig()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }


    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }


    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.' . $this->invitation->user_id)];
    }


    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }


    private function payload(): array
    {
        $inviterName = trim(($this->inviter->first_name ?? '') . ' ' . ($this->inviter->last_name ?? ''));

        return [
            'invitation_id' => $this->invitation->id,
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_logo_url' => $this->group->logo_url,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $inviterName,
            'message' => 'invited you to join ' . $this->group->name,
            // 'message' => $inviterName . ' invited you to join ' . $this->group->name,
            'type' => 'group_invitation',
            'sent_at' => now()->toIso8601String(),
        ];
    }


    private function hasValidPusherBroadcastConfig(): bool
    {
        return config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.app_id'))
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'));
    }
}
