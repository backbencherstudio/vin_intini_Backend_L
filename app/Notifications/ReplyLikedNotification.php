<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

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

    public function toDatabase($notifiable)
    {
        return [
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'reply_id'    => $this->reply->id,
            'comment_id'  => $this->reply->comment_id,
            'message'     => 'liked your reply',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'sender_id'   => $this->sender->id,
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'reply_id'    => $this->reply->id,
            'comment_id'  => $this->reply->comment_id,
            'message'     => 'liked your reply',
        ]);
    }
}