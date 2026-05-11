<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CommentRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $sender;
    protected $post;
    protected $comment;

    public function __construct($sender, $post, $comment)
    {
        $this->sender = $sender;
        $this->post = $post;
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
            'post_id'      => $this->post->id,
            'comment_id'   => $this->comment->id,
            'message'      => 'replied to your comment',
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