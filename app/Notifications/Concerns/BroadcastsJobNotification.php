<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Models\Industry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;

trait BroadcastsJobNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->hasValidPusherBroadcastConfig()) {
            $channels[] = 'broadcast';
        }

        if (method_exists($notifiable, 'routeNotificationForFcm') && $notifiable->routeNotificationForFcm($this) !== []) {
            $channels[] = \NotificationChannels\Fcm\FcmChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        $recipientIds = array_values(array_unique(array_filter([
            $this->job->created_by,
            $this->resolveIndustry()?->created_by,
        ])));

        foreach ($recipientIds as $recipientId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$recipientId);
        }

        return $channels;
    }

    protected function resolveIndustry(): ?Industry
    {
        return $this->job->relationLoaded('industry')
            ? $this->job->industry
            : $this->job->industry()->first();
    }

    protected function unreadNotificationCount(object $notifiable): int
    {
        return method_exists($notifiable, 'unreadNotifications')
            ? (int) $notifiable->unreadNotifications()->count()
            : 0;
    }

    private function hasValidPusherBroadcastConfig(): bool
    {
        return config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.app_id'))
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'));
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function notificationData(object $notifiable): array;
}

