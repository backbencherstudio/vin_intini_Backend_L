<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryProduct;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ProductRejectedNotification extends Notification
{
    public function __construct(
        public IndustryProduct $product,
        public string $reason,
        public ?User $admin = null
    ) {}

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
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationData(object $notifiable): array
    {
        $unreadCount = method_exists($notifiable, 'unreadNotifications')
            ? $notifiable->unreadNotifications()->count()
            : 0;

        $industry = $this->product->relationLoaded('industry')
            ? $this->product->industry
            : $this->product->industry()->first();

        return [
            'product_id' => $this->product->id,
            'product_unique_id' => $this->product->product_id,
            'product_name' => $this->product->product_name,
            'industry_id' => $this->product->industry_id,
            'industry_name' => $industry?->name,
            'industry_slug' => $industry?->slug,
            'status' => 'rejected',
            'rejection_reason' => $this->reason,
            'rejected_by' => $this->admin?->id,
            'sender_name' => 'MindUnite Admin',
            'sender_username' => null,
            'sender_profile_image_url' => null,
            'action_url' => '/mu/products',
            'message' => "rejected your product \"{$this->product->product_name}\" and hid it from the platform.",
            'description' => "Reason: {$this->reason}",
            'type' => class_basename(self::class),
            'rejected_at' => ($this->product->rejected_at ?? now())->toIso8601String(),
            'unread_count' => $unreadCount + 1,
        ];
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
            $this->product->creator_id,
            $this->product->industry?->created_by,
        ])));

        foreach ($recipientIds as $recipientId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$recipientId);
        }

        return $channels;
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title('Product Rejected')
                    ->body("Your product \"{$this->product->product_name}\" was rejected. Reason: {$this->reason}")
            )
            ->data([
                'product_id' => (string) $this->product->id,
                'product_unique_id' => (string) ($this->product->product_id ?? ''),
                'industry_id' => (string) ($this->product->industry_id ?? ''),
                'status' => 'rejected',
                'rejection_reason' => $this->reason,
                'action_url' => '/mu/products',
                'type' => class_basename(self::class),
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

