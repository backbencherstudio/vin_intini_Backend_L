<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PostLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $sender;

    protected $post;

    public function __construct($sender, $post)
    {
        $this->sender = $sender;
        $this->post = $post;
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
            'post_id' => $this->post->id,
            'type' => class_basename(self::class),
            'message' => 'liked your post',
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
