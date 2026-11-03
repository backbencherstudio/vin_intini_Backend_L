<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IndustryMessageReactionChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public array $reactions,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('industry-conversation.'.$this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'IndustryMessageReactionChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'reactions' => $this->reactions,
        ];
    }
}

