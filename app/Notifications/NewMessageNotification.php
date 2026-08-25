<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via(User $notifiable): array
    {
        return $notifiable->routeNotificationForFcm($this) !== []
            ? [FcmChannel::class]
            : [];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $sender = $this->message->sender;
        $senderImage = $sender->notificationImageUrl();

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title($sender->first_name)
                    ->body($this->message->type === 'text'
                        ? $this->message->message
                        : 'Sent a '.$this->message->type)
                    ->image($senderImage)
            )
            ->data([
                'conversation_id' => (string) $this->message->conversation_id,
                'message_id' => (string) $this->message->id,
                'sender_id' => (string) $sender->id,
                'sender_name' => trim(($sender->first_name ?? '').' '.($sender->last_name ?? '')),
                'type' => $this->message->type,
            ]);
    }
}
