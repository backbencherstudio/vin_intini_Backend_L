<?php

namespace App\Events;

use App\Models\IndustryConversation;
use App\Models\IndustryMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IndustryConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IndustryConversation $conversation,
        public int $receiverId,
        public int $unreadCount,
        public ?IndustryMessage $message = null,
        public int $unreadConversationCount = 0,
        public int $totalUnreadMessages = 0,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->receiverId),
            new PrivateChannel('industry.'.$this->conversation->industry_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'IndustryConversationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'industry_id' => $this->conversation->industry_id,
            'unread_count' => $this->unreadCount,
            'unread_conversation_count' => $this->unreadConversationCount,
            'total_unread_messages' => $this->totalUnreadMessages,
            'last_message' => $this->message instanceof IndustryMessage ? [
                'id' => $this->message->id,
                'type' => $this->message->display_type,
                'message' => $this->message->message,
                'file_url' => $this->message->file_url,
                'file_name' => $this->message->file_name,
                'sender_type' => $this->message->sender_type,
                'sender_id' => $this->message->sender_id,
                'created_at' => $this->message->created_at->toISOString(),
            ] : null,
        ];
    }
}
