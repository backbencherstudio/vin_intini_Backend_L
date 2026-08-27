<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $receiver,
        public int $unreadCount,
        public ?Message $message = null,
        public int $unreadConversationCount = 0,
        public int $totalUnreadMessages = 0,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->receiver->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationUpdated';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'conversation_id' => $this->conversation->id,
            'unread_count' => $this->unreadCount,
            'unread_conversation_count' => $this->unreadConversationCount,
            'total_unread_messages' => $this->totalUnreadMessages,
            'user' => null,
            'last_message' => null,
        ];

        if ($this->message) {
            $otherUser = $this->conversation->getOtherUser($this->receiver->id);

            $payload['user'] = [
                'id' => $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                'profile_image_url' => $otherUser->profile_image_url,
                'has_premium' => $otherUser->subscriptions()->whereIn('status', ['active', 'trialing', 'paused'])->exists(),
            ];
            $payload['last_message'] = [
                'id' => $this->message->id,
                'type' => $this->message->display_type,
                'message' => $this->message->message,
                'file_url' => $this->message->file_url,
                'file_name' => $this->message->file_name,
                'sender_id' => $this->message->sender_id,
                'created_at' => $this->message->created_at?->toISOString(),
            ];
        }

        return $payload;
    }
}
