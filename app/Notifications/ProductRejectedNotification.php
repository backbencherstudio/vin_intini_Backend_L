<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryProduct;
use App\Models\User;
use App\Notifications\Concerns\BroadcastsProductNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ProductRejectedNotification extends Notification
{
    use BroadcastsProductNotification;

    public function __construct(
        public IndustryProduct $product,
        public string $reason,
        public ?User $admin = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function notificationData(object $notifiable): array
    {
        $industry = $this->resolveIndustry();

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
            'unread_count' => $this->unreadNotificationCount($notifiable) + 1,
        ];
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
}

