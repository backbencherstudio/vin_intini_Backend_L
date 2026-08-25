<?php

namespace App\Notifications;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ConnectionRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Connection $connectionRequest,
        public User $acceptor
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->hasValidPusherBroadcastConfig()) {
            $channels[] = 'broadcast';
        }

        if ($notifiable->routeNotificationForFcm($this) !== []) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    private function notificationData($notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        $acceptorName = trim(
            ($this->acceptor->first_name ?? '').' '.
            ($this->acceptor->last_name ?? '')
        );

        return [
            'connection_request_id' => $this->connectionRequest->id,
            'acceptor_id' => $this->acceptor->id,
            'acceptor_username' => $this->acceptor->username,
            'acceptor_name' => $acceptorName,
            'acceptor_profile_image' => $this->acceptor->profile_image,
            'acceptor_profile_image_url' => $this->acceptor->profile_image_url,
            'message' => 'accepted your connection request',
            'type' => class_basename(self::class),
            'responded_at' => $this->connectionRequest->responded_at?->toIso8601String(),
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
        return [
            new PrivateChannel(
                'App.Models.User.'.$this->connectionRequest->sender_id
            ),
        ];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $acceptorName = trim(
            ($this->acceptor->first_name ?? '').' '.
            ($this->acceptor->last_name ?? '')
        );

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title($acceptorName)
                    ->body('accepted your connection request')
            )
            ->data([
                'connection_request_id' => (string) $this->connectionRequest->id,
                'acceptor_id' => (string) $this->acceptor->id,
                'acceptor_username' => (string) $this->acceptor->username,
                'acceptor_name' => $acceptorName,
                'sender_image' => $this->acceptor->notificationImageUrl(),
                'type' => class_basename(self::class),
                'responded_at' => $this->connectionRequest->responded_at?->toIso8601String(),
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
