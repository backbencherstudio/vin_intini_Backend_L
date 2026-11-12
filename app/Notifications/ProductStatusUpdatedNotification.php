<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryProduct;
use App\Models\User;
use App\Notifications\Concerns\BroadcastsProductNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ProductStatusUpdatedNotification extends Notification
{
    use BroadcastsProductNotification;

    public function __construct(
        public IndustryProduct $product,
        public string $previousStatus,
        public string $newStatus,
        public ?User $admin = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function notificationData(object $notifiable): array
    {
        $industry = $this->resolveIndustry();
        $formattedNewStatus = ucfirst($this->newStatus);
        $formattedPrevStatus = ucfirst($this->previousStatus);

        $message = $this->newStatus === 'active'
            ? "restored and activated your previously rejected product \"{$this->product->product_name}\" on the platform."
            : "changed your previously rejected product \"{$this->product->product_name}\" status from {$this->previousStatus} to {$this->newStatus}.";

        return [
            'product_id' => $this->product->id,
            'product_unique_id' => $this->product->product_id,
            'product_name' => $this->product->product_name,
            'industry_id' => $this->product->industry_id,
            'industry_name' => $industry?->name,
            'industry_slug' => $industry?->slug,
            'previous_status' => $this->previousStatus,
            'status' => $this->newStatus,
            'updated_by' => $this->admin?->id,
            'sender_name' => 'MindUnite Admin',
            'sender_username' => null,
            'sender_profile_image_url' => null,
            'action_url' => '/mu/products',
            'message' => $message,
            'description' => "Status changed by Admin from {$formattedPrevStatus} to {$formattedNewStatus}.",
            'type' => class_basename(self::class),
            'updated_at' => now()->toIso8601String(),
            'unread_count' => $this->unreadNotificationCount($notifiable) + 1,
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title('Product Status Updated')
                    ->body("Your previously rejected product \"{$this->product->product_name}\" is now {$this->newStatus}.")
            )
            ->data([
                'product_id' => (string) $this->product->id,
                'product_unique_id' => (string) ($this->product->product_id ?? ''),
                'industry_id' => (string) ($this->product->industry_id ?? ''),
                'previous_status' => $this->previousStatus,
                'status' => $this->newStatus,
                'action_url' => '/mu/products',
                'type' => class_basename(self::class),
            ]);
    }
}

