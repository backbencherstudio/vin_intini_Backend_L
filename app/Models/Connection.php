<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    protected $table = 'connections';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeIgnored(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IGNORED);
    }
}
