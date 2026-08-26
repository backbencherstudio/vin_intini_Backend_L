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
        'user_1_cleared_message_id',
        'user_2_cleared_message_id',
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
            'user_1_cleared_message_id' => 'integer',
            'user_2_cleared_message_id' => 'integer',
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
        if ($this->isArchivedFor($userId)) {
            return 0;
        }

        $lastReadAt = $userId === $this->user_id_1
            ? $this->user_1_last_read_at
            : $this->user_2_last_read_at;

        return $this->visibleMessagesFor($userId)
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();
    }

    /**
     * Messages visible to the given user.
     *
     * Everything up to the message that was the latest when the user deleted
     * the conversation stays permanently hidden, even after the conversation
     * is restored. Newer messages become visible normally.
     */
    public function visibleMessagesFor(int $userId): HasMany
    {
        return $this->messages()
            ->when(
                $this->clearedMessageIdFor($userId),
                fn (Builder $q, int $clearedMessageId) => $q->where('messages.id', '>', $clearedMessageId)
            );
    }

    public function clearedMessageIdFor(int $userId): ?int
    {
        if ($userId === $this->user_id_1) {
            return $this->user_1_cleared_message_id;
        }

        return $userId === $this->user_id_2 ? $this->user_2_cleared_message_id : null;
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
     *
     * The deletion also permanently hides all existing history for this user:
     * if the conversation is restored later, only messages created afterwards
     * become visible to them.
     */
    public function deleteFor(int $userId): void
    {
        if ($userId === $this->user_id_1) {
            $this->user_1_deleted_at = now();
            $this->user_1_cleared_message_id = $this->messages()->max('id');
        } elseif ($userId === $this->user_id_2) {
            $this->user_2_deleted_at = now();
            $this->user_2_cleared_message_id = $this->messages()->max('id');
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
            ->whereNull('messages.deleted_at')
            ->whereIn('messages.conversation_id', static::forUser($userId)->notDeletedFor($userId)->notArchivedFor($userId)->pluck('id'))
            ->where('messages.sender_id', '!=', $userId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q) use ($userId) {
                    $q->where('conversations.user_id_1', $userId)
                        ->where(function ($q) {
                            $q->where(function ($q) {
                                $q->whereColumn('messages.created_at', '>', 'conversations.user_1_last_read_at')
                                    ->orWhereNull('conversations.user_1_last_read_at');
                            })->where(function ($q) {
                                $q->whereNull('conversations.user_1_cleared_message_id')
                                    ->orWhereColumn('messages.id', '>', 'conversations.user_1_cleared_message_id');
                            });
                        });
                })->orWhere(function ($q) use ($userId) {
                    $q->where('conversations.user_id_2', $userId)
                        ->where(function ($q) {
                            $q->where(function ($q) {
                                $q->whereColumn('messages.created_at', '>', 'conversations.user_2_last_read_at')
                                    ->orWhereNull('conversations.user_2_last_read_at');
                            })->where(function ($q) {
                                $q->whereNull('conversations.user_2_cleared_message_id')
                                    ->orWhereColumn('messages.id', '>', 'conversations.user_2_cleared_message_id');
                            });
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
