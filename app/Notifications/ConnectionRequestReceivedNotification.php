<?php

namespace App\Notifications;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestReceivedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Connection $connectionRequest,
        public User $sender
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->hasValidPusherBroadcastConfig()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.' . $this->connectionRequest->receiver_id)];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    private function payload(): array
    {
        return [
            'connection_request_id' => $this->connectionRequest->id,
            'sender_id' => $this->sender->id,
            'sender_name' => trim(($this->sender->first_name ?? '') . ' ' . ($this->sender->last_name ?? '')),
            'sender_profile_image' => $this->sender->profile_image,
            'sender_profile_image_url' => $this->sender->profile_image_url,
            'message' => trim(($this->sender->first_name ?? '') . ' ' . ($this->sender->last_name ?? '')) . ' sent you a connection request',
            'type' => 'connection_request_received',
            'requested_at' => $this->connectionRequest->created_at?->toDateTimeString(),
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
