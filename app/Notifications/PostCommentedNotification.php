<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PostCommentedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected $sender, protected $post, protected $comment) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function notificationData($notifiable): array
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
            'comment_id' => $this->comment->id,
            'type' => class_basename(self::class),
            'message' => 'commented on your post',
            'unread_count' => $unreadCount + 1,
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->notificationData($notifiable);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            $this->notificationData($notifiable)
        );
    }
}
