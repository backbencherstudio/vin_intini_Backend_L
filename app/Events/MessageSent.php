<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'type' => $this->message->type,
            'message' => $this->message->message,
            'file_url' => $this->message->file_url,
            'file_name' => $this->message->file_name,
            'file_size' => $this->message->file_size,
            'file_extension' => $this->message->file_extension,
            'file_category' => $this->message->file_category,
            'duration' => $this->message->duration,
            'reply_to' => $this->message->replyTo ? [
                'id' => $this->message->replyTo->id,
                'sender_id' => $this->message->replyTo->sender_id,
                'type' => $this->message->replyTo->type,
                'message' => $this->message->replyTo->message,
                'file_url' => $this->message->replyTo->file_url,
                'file_name' => $this->message->replyTo->file_name,
                'file_category' => $this->message->replyTo->file_category,
            ] : null,            'created_at' => $this->message->created_at->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
