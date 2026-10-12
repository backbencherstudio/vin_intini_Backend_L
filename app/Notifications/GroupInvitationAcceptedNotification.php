<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class GroupInvitationAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $acceptor,
        public ?User $inviter = null
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

    private function notificationData(object $notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        $acceptorName = trim(
            ($this->acceptor->first_name ?? '').' '.
            ($this->acceptor->last_name ?? '')
        );

        return [
            'group_id' => $this->group->id,
            'group_slug' => $this->group->slug,
            'group_name' => $this->group->name,
            'group_logo_url' => $this->group->logo_url,
            'sender_id' => $this->acceptor->id,
            'sender_username' => $this->acceptor->username,
            'sender_name' => $acceptorName,
            'sender_profile_image_url' => $this->acceptor->profile_image_url,
            'acceptor_id' => $this->acceptor->id,
            'acceptor_username' => $this->acceptor->username,
            'acceptor_name' => $acceptorName,
            'acceptor_profile_image_url' => $this->acceptor->profile_image_url,
            'message' => 'accepted your invitation to join '.$this->group->name,
            'type' => class_basename(self::class),
            'responded_at' => now()->toIso8601String(),
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
        $targetUserId = $this->inviter?->id;

        if ($targetUserId) {
            return [
                new PrivateChannel('App.Models.User.'.$targetUserId),
            ];
        }

        return [];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $acceptorName = trim(
            ($this->acceptor->first_name ?? '').' '.
            ($this->acceptor->last_name ?? '')
        );

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title($acceptorName)
                    ->body('accepted your invitation to join '.$this->group->name)
            )
            ->data([
                'group_id' => (string) $this->group->id,
                'group_slug' => (string) $this->group->slug,
                'group_name' => (string) $this->group->name,
                'sender_id' => (string) $this->acceptor->id,
                'sender_username' => (string) $this->acceptor->username,
                'sender_name' => $acceptorName,
                'sender_image' => $this->acceptor->notificationImageUrl(),
                'acceptor_id' => (string) $this->acceptor->id,
                'acceptor_username' => (string) $this->acceptor->username,
                'acceptor_name' => $acceptorName,
                'type' => class_basename(self::class),
                'responded_at' => now()->toIso8601String(),
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
