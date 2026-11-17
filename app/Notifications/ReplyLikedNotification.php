<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ReplyLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected $sender, protected $reply) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    protected function notificationData($notifiable): array
    {
        $unreadCount = $notifiable
            ->unreadNotifications()
            ->count();

        $postId = $this->reply->post_id ?? $this->reply->comment?->post_id;
        $parentCommentId = $this->reply->comment_id ?? null;
        $replyId = $this->reply->id;

        return [
            'sender_id' => $this->sender->id,
            'sender_username' => $this->sender->username,
            'sender_name' => $this->sender->first_name.' '.$this->sender->last_name,
            'sender_profile_image_url' => $this->sender->profile_image_url,
            'post_id' => $postId,
            'reply_id' => $replyId,
            'comment_id' => $parentCommentId,
            'action_url' => $postId ? '/mu/post/'.$postId.'?comment_id='.$parentCommentId.'&reply_id='.$replyId : null,
            'type' => class_basename(self::class),
            'message' => 'liked your reply',
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
