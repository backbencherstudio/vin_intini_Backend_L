<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionRemoved implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $removedUser,
        public int $removedById,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConnectionRemoved';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'removed_by' => $this->removedById,
            'user' => [
                'id' => $this->removedUser->id,
                'name' => trim(($this->removedUser->first_name ?? '').' '.($this->removedUser->last_name ?? '')),
                'profile_image_url' => $this->removedUser->profile_image_url,
            ],
            'is_connected' => false,
            'is_following' => false,
        ];
    }
}
