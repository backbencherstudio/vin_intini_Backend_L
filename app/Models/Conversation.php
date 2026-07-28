<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id_1',
        'user_id_2',
        'last_message_id',
        'user_1_last_read_at',
        'user_2_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'user_1_last_read_at' => 'datetime',
            'user_2_last_read_at' => 'datetime',
        ];
    }

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_1');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_2');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function getOtherUser(int $currentUserId): User
    {
        return $currentUserId === $this->user_id_1 ? $this->user2 : $this->user1;
    }

    public function unreadCountFor(int $userId): int
    {
        $lastReadAt = $userId === $this->user_id_1
            ? $this->user_1_last_read_at
            : $this->user_2_last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();
    }

    public function markAsReadFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_last_read_at = now();
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_last_read_at = now();
        }
        $this->save();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id_1', $userId)->orWhere('user_id_2', $userId);
    }

    public static function betweenUsers(int $a, int $b): self
    {
        return static::firstOrCreate([
            'user_id_1' => min($a, $b),
            'user_id_2' => max($a, $b),
        ]);
    }
}
