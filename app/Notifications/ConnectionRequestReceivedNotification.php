<?php

namespace App\Notifications;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConnectionRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Connection $connectionRequest,
        public User $sender
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

        return [
            'connection_request_id' => $this->connectionRequest->id,
            'sender_id'             => $this->sender->id,
            'sender_name'           => trim(
                ($this->sender->first_name ?? '') . ' ' .
                ($this->sender->last_name ?? '')
            ),
            'sender_profile_image'      => $this->sender->profile_image,
            'sender_profile_image_url'  => $this->sender->profile_image_url,
            'message'               => 'sent you a connection request',
            'type'                  => class_basename(self::class),
            'requested_at'          => $this->connectionRequest->created_at?->toIso8601String(),
            'unread_count'          => $unreadCount + 1,
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
                'App.Models.User.' . $this->connectionRequest->receiver_id
            )
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
