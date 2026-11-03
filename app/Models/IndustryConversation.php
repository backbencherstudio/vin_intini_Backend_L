<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class IndustryConversation extends Model
{
    protected $fillable = [
        'industry_id',
        'user_id',
        'last_message_id',
        'industry_last_read_at',
        'user_last_read_at',
        'industry_archived_at',
        'user_archived_at',
        'industry_deleted_at',
        'user_deleted_at',
        'industry_cleared_message_id',
        'user_cleared_message_id',
    ];

    protected function casts(): array
    {
        return [
            'industry_last_read_at' => 'datetime',
            'user_last_read_at' => 'datetime',
            'industry_archived_at' => 'datetime',
            'user_archived_at' => 'datetime',
            'industry_deleted_at' => 'datetime',
            'user_deleted_at' => 'datetime',
            'industry_cleared_message_id' => 'integer',
            'user_cleared_message_id' => 'integer',
        ];
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IndustryMessage::class, 'industry_conversation_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(IndustryMessage::class, 'last_message_id');
    }

    public function visibleMessagesForIndustry(): HasMany
    {
        return $this->messages()
            ->when(
                $this->industry_cleared_message_id,
                fn (Builder $q, int $clearedId) => $q->where('industry_messages.id', '>', $clearedId)
            );
    }

    public function visibleMessagesForUser(): HasMany
    {
        return $this->messages()
            ->when(
                $this->user_cleared_message_id,
                fn (Builder $q, int $clearedId) => $q->where('industry_messages.id', '>', $clearedId)
            );
    }

    public function unreadCountForIndustry(): int
    {
        if ($this->isArchivedForIndustry()) {
            return 0;
        }

        return $this->visibleMessagesForIndustry()
            ->where('sender_type', '!=', 'industry')
            ->when($this->industry_last_read_at, fn ($q) => $q->where('created_at', '>', $this->industry_last_read_at))
            ->count();
    }

    public function unreadCountForUser(): int
    {
        if ($this->isArchivedForUser()) {
            return 0;
        }

        return $this->visibleMessagesForUser()
            ->where('sender_type', 'industry')
            ->when($this->user_last_read_at, fn ($q) => $q->where('created_at', '>', $this->user_last_read_at))
            ->count();
    }

    public function markAsReadForIndustry(): void
    {
        $this->industry_last_read_at = now();
        $this->saveWithoutTouchingTimestamps();
    }

    public function markAsReadForUser(): void
    {
        $this->user_last_read_at = now();
        $this->saveWithoutTouchingTimestamps();
    }

    public function markAsUnreadForIndustry(): void
    {
        $this->industry_last_read_at = null;
        $this->saveWithoutTouchingTimestamps();
    }

    public function markAsUnreadForUser(): void
    {
        $this->user_last_read_at = null;
        $this->saveWithoutTouchingTimestamps();
    }

    public function archiveForIndustry(): void
    {
        $this->industry_archived_at = now();
        $this->save();
    }

    public function unarchiveForIndustry(): void
    {
        $this->industry_archived_at = null;
        $this->save();
    }

    public function isArchivedForIndustry(): bool
    {
        return $this->industry_archived_at !== null;
    }

    public function archiveForUser(): void
    {
        $this->user_archived_at = now();
        $this->save();
    }

    public function unarchiveForUser(): void
    {
        $this->user_archived_at = null;
        $this->save();
    }

    public function isArchivedForUser(): bool
    {
        return $this->user_archived_at !== null;
    }

    public function deleteForIndustry(): void
    {
        $this->industry_deleted_at = now();
        $this->industry_cleared_message_id = $this->messages()->max('id');
        $this->save();
    }

    public function deleteForUser(): void
    {
        $this->user_deleted_at = now();
        $this->user_cleared_message_id = $this->messages()->max('id');
        $this->save();
    }

    public function restoreForIndustry(): void
    {
        $this->industry_deleted_at = null;
        if ($this->isDirty()) {
            $this->save();
        }
    }

    public function restoreForUser(): void
    {
        $this->user_deleted_at = null;
        if ($this->isDirty()) {
            $this->save();
        }
    }

    public function isDeletedForIndustry(): bool
    {
        return $this->industry_deleted_at !== null;
    }

    public function isDeletedForUser(): bool
    {
        return $this->user_deleted_at !== null;
    }

    protected function saveWithoutTouchingTimestamps(): void
    {
        $this->timestamps = false;
        $this->save();
        $this->timestamps = true;
    }

    public function scopeForIndustry(Builder $query, int $industryId): Builder
    {
        return $query->where('industry_id', $industryId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNotDeletedForIndustry(Builder $query): Builder
    {
        return $query->whereNull('industry_deleted_at');
    }

    public function scopeNotDeletedForUser(Builder $query): Builder
    {
        return $query->whereNull('user_deleted_at');
    }

    public function scopeNotArchivedForIndustry(Builder $query): Builder
    {
        return $query->whereNull('industry_archived_at');
    }

    public function scopeNotArchivedForUser(Builder $query): Builder
    {
        return $query->whereNull('user_archived_at');
    }

    public function scopeArchivedOnlyForIndustry(Builder $query): Builder
    {
        return $query->whereNotNull('industry_archived_at');
    }

    public function scopeArchivedOnlyForUser(Builder $query): Builder
    {
        return $query->whereNotNull('user_archived_at');
    }

    public static function between(int $industryId, int $userId): self
    {
        return static::firstOrCreate([
            'industry_id' => $industryId,
            'user_id' => $userId,
        ]);
    }

    public static function unreadSummaryForIndustry(int $industryId): array
    {
        $counts = DB::table('industry_messages')
            ->join('industry_conversations', 'industry_messages.industry_conversation_id', '=', 'industry_conversations.id')
            ->whereNull('industry_messages.deleted_at')
            ->where('industry_conversations.industry_id', $industryId)
            ->whereNull('industry_conversations.industry_deleted_at')
            ->whereNull('industry_conversations.industry_archived_at')
            ->where('industry_messages.sender_type', '!=', 'industry')
            ->where(function ($q) {
                $q->whereColumn('industry_messages.created_at', '>', 'industry_conversations.industry_last_read_at')
                    ->orWhereNull('industry_conversations.industry_last_read_at');
            })
            ->where(function ($q) {
                $q->whereNull('industry_conversations.industry_cleared_message_id')
                    ->orWhereColumn('industry_messages.id', '>', 'industry_conversations.industry_cleared_message_id');
            })
            ->groupBy('industry_messages.industry_conversation_id')
            ->selectRaw('industry_messages.industry_conversation_id, COUNT(*) as count')
            ->get()
            ->pluck('count', 'industry_conversation_id')
            ->toArray();

        return [
            'unread_conversation_count' => count(array_filter($counts)),
            'total_unread_messages' => (int) array_sum($counts),
        ];
    }
}

