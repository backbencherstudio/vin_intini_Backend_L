<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Conversation extends Model
{
    protected $fillable = [
        'user_id_1',
        'user_id_2',
        'last_message_id',
        'user_1_last_read_at',
        'user_2_last_read_at',
        'user_1_archived_at',
        'user_2_archived_at',
        'user_1_deleted_at',
        'user_2_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'user_1_last_read_at' => 'datetime',
            'user_2_last_read_at' => 'datetime',
            'user_1_archived_at' => 'datetime',
            'user_2_archived_at' => 'datetime',
            'user_1_deleted_at' => 'datetime',
            'user_2_deleted_at' => 'datetime',
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

    public function markAsUnreadFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_last_read_at = null;
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_last_read_at = null;
        }
        $this->save();
    }

    public function archiveFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_archived_at = now();
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_archived_at = now();
        }
        $this->save();
    }

    public function unarchiveFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_archived_at = null;
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_archived_at = null;
        }
        $this->save();
    }

    public function isArchivedFor(int $userId): bool
    {
        $archivedAt = $userId === $this->user_id_1
            ? $this->user_1_archived_at
            : $this->user_2_archived_at;

        return $archivedAt !== null;
    }

    /**
     * Hide the conversation for the given user without affecting the other party.
     */
    public function deleteFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_deleted_at = now();
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_deleted_at = now();
        }
        $this->save();
    }

    /**
     * Make the conversation visible again for the given user.
     */
    public function restoreFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_deleted_at = null;
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_deleted_at = null;
        }

        if ($this->isDirty()) {
            $this->save();
        }
    }

    public function isDeletedFor(int $userId): bool
    {
        $deletedAt = $userId === $this->user_id_1
            ? $this->user_1_deleted_at
            : $this->user_2_deleted_at;

        return $deletedAt !== null;
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
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id_1', $userId)->orWhere('user_id_2', $userId);
        });
    }

    public function scopeNotDeletedFor(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id_1', $userId)->whereNull('user_1_deleted_at')
                ->orWhere('user_id_2', $userId)->whereNull('user_2_deleted_at');
        });
    }

    public function scopeNotArchivedFor(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id_1', $userId)->whereNull('user_1_archived_at')
                ->orWhere('user_id_2', $userId)->whereNull('user_2_archived_at');
        });
    }

    public function scopeArchivedOnlyFor(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id_1', $userId)->whereNotNull('user_1_archived_at')
                ->orWhere('user_id_2', $userId)->whereNotNull('user_2_archived_at');
        });
    }

    public static function betweenUsers(int $a, int $b): self
    {
        return static::firstOrCreate([
            'user_id_1' => min($a, $b),
            'user_id_2' => max($a, $b),
        ]);
    }

    /**
     * Unread message count per conversation for a user.
     *
     * @return array<int, int>
     */
    public static function unreadCountsFor(int $userId): array
    {
        return DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereIn('messages.conversation_id', static::forUser($userId)->notDeletedFor($userId)->pluck('id'))
            ->where('messages.sender_id', '!=', $userId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q) use ($userId) {
                    $q->where('conversations.user_id_1', $userId)
                        ->where(function ($q) {
                            $q->whereColumn('messages.created_at', '>', 'conversations.user_1_last_read_at')
                                ->orWhereNull('conversations.user_1_last_read_at');
                        });
                })->orWhere(function ($q) use ($userId) {
                    $q->where('conversations.user_id_2', $userId)
                        ->where(function ($q) {
                            $q->whereColumn('messages.created_at', '>', 'conversations.user_2_last_read_at')
                                ->orWhereNull('conversations.user_2_last_read_at');
                        });
                });
            })
            ->groupBy('messages.conversation_id')
            ->selectRaw('messages.conversation_id, COUNT(*) as count')
            ->get()
            ->pluck('count', 'conversation_id')
            ->toArray();
    }

    /**
     * Global unread summary: how many conversations have unread messages
     * and how many unread messages exist in total.
     *
     * @return array{unread_conversation_count: int, total_unread_messages: int}
     */
    public static function unreadSummaryFor(int $userId): array
    {
        $counts = static::unreadCountsFor($userId);

        return [
            'unread_conversation_count' => count(array_filter($counts)),
            'total_unread_messages' => (int) array_sum($counts),
        ];
    }
}
