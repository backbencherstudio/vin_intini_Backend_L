<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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

        if (method_exists($notifiable, 'routeNotificationForFcm') && $notifiable->routeNotificationForFcm($this) !== []) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    private function notificationData($notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        $inviterName = trim(
            ($this->inviter->first_name ?? '').' '.
            ($this->inviter->last_name ?? '')
        );

        return [
            'invitation_id' => $this->invitation->id,
            'group_id' => $this->group->id,
            'group_slug' => $this->group->slug,
            'group_name' => $this->group->name,
            'group_logo_url' => $this->group->logo_url,
            'sender_id' => $this->inviter->id,
            'sender_username' => $this->inviter->username,
            'sender_name' => $inviterName,
            'sender_profile_image_url' => $this->inviter->profile_image_url,
            'inviter_id' => $this->inviter->id,
            'inviter_username' => $this->inviter->username,
            'inviter_name' => $inviterName,
            'message' => 'invited you to join '.$this->group->name,
            'type' => class_basename(self::class),
            'sent_at' => now()->toIso8601String(),
            'unread_count' => $unreadCount + 1,
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
        $targetUserId = $this->invitation->invited_user_id ?? $this->invitation->user_id;

        return [
            new PrivateChannel('App.Models.User.'.$targetUserId),
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $inviterName = trim(
            ($this->inviter->first_name ?? '').' '.
            ($this->inviter->last_name ?? '')
        );

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title($inviterName)
                    ->body('invited you to join '.$this->group->name)
            )
            ->data([
                'invitation_id' => (string) $this->invitation->id,
                'group_id' => (string) $this->group->id,
                'group_slug' => (string) $this->group->slug,
                'group_name' => (string) $this->group->name,
                'sender_id' => (string) $this->inviter->id,
                'sender_username' => (string) $this->inviter->username,
                'sender_name' => $inviterName,
                'sender_image' => $this->inviter->notificationImageUrl(),
                'type' => class_basename(self::class),
                'sent_at' => now()->toIso8601String(),
            ]);
    }

    private function hasValidPusherBroadcastConfig(): bool
    {
        return config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.app_id'))
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'));
    }
}
