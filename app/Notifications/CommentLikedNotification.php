<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CommentLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $sender;
    protected $comment;

    public function __construct($sender, $comment)
    {
        $this->sender = $sender;
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }


    private function notificationData($notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        return [
            'sender_id'    => $this->sender->id,
            'sender_name'  => trim(
                ($this->sender->first_name ?? '') . ' ' .
                ($this->sender->last_name ?? '')
            ),
            'comment_id'   => $this->comment->id,
            'post_id'      => $this->comment->post_id,
            'message'      => 'liked your comment',
            'type'         => class_basename(self::class),
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