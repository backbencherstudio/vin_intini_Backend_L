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
use Illuminate\Contracts\Queue\ShouldQueue;

class GroupInvitationNotification extends Notification implements ShouldQueue
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

    private function notificationData($notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        $inviterName = trim(
            ($this->inviter->first_name ?? '') . ' ' .
            ($this->inviter->last_name ?? '')
        );

        return [
            'invitation_id' => $this->invitation->id,
            'group_id'      => $this->group->id,
            'group_name'    => $this->group->name,
            'group_logo_url'=> $this->group->logo_url,
            'inviter_id'    => $this->inviter->id,
            'inviter_name'  => $inviterName,
            // Fallback to a default profile image URL if the inviter doesn't have one
            'sender_name'  => $inviterName,
            'sender_profile_image_url'  => $this->inviter->profile_image_url,

            'message'       => 'invited you to join ' . $this->group->name,
            'type'          => class_basename(self::class),
            'sent_at'       => now()->toIso8601String(),
            'unread_count'  => $unreadCount + 1,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationData($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            $this->notificationData($notifiable)
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->invitation->user_id)
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
