<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ReplyLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $sender;

    protected $reply;

    public function __construct($sender, $reply)
    {
        $this->sender = $sender;
        $this->reply = $reply;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    protected function notificationData($notifiable)
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        return [
            'sender_id' => $this->sender->id,
            'sender_username' => $this->sender->username,
            'sender_name' => $this->sender->first_name.' '.$this->sender->last_name,
            'sender_profile_image_url' => $this->sender->profile_image_url,
            'reply_id' => $this->reply->id,
            'comment_id' => $this->reply->comment_id,
            'type' => class_basename(self::class),
            'message' => 'liked your reply',
            'unread_count' => $unreadCount + 1,
        ];
    }

    public function toDatabase($notifiable)
    {
        return $this->notificationData($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage(
            $this->notificationData($notifiable)
        );
    }
}
