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

    public function toDatabase($notifiable)
    {
        return [
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'comment_id'  => $this->comment->id,
            'post_id'     => $this->comment->post_id,
            'message'     => 'liked your comment',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'comment_id'  => $this->comment->id,
            'post_id'     => $this->comment->post_id,
            'message'     => 'liked your comment',
        ]);
    }
}