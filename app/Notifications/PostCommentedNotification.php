<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PostCommentedNotification extends Notification implements ShouldQueue
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

    public function toDatabase($notifiable)
    {
        return [
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'post_id'     => $this->post->id,
            'comment_id'  => $this->comment->id,
            'message'     => 'commented on your post',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'post_id'     => $this->post->id,
            'comment_id'  => $this->comment->id,
            'message'     => 'commented on your post',
        ]);
    }
}