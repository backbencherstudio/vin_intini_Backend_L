<?php

namespace App\Notifications;

use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ConnectionRequest $connectionRequest,
        public User $acceptor
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
        return [new PrivateChannel('App.Models.User.' . $this->connectionRequest->sender_id)];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    private function payload(): array
    {
        $acceptorName = trim(($this->acceptor->first_name ?? '') . ' ' . ($this->acceptor->last_name ?? ''));

        return [
            'connection_request_id' => $this->connectionRequest->id,
            'acceptor_id' => $this->acceptor->id,
            'acceptor_name' => $acceptorName,
            'acceptor_profile_image' => $this->acceptor->profile_image,
            'acceptor_profile_image_url' => $this->acceptor->profile_image_url,
            'message' => $acceptorName . ' accepted your connection request',
            'type' => 'connection_request_accepted',
            'responded_at' => $this->connectionRequest->responded_at?->toDateTimeString(),
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
