<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    public const STATUS_UNREAD = 'unread';

    public const STATUS_READ = 'read';

    public const STATUS_REPLIED = 'replied';

    protected $table = 'contact_us';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'address',
        'status',
        'read_at',
        'replied_at',
        'admin_note',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function markAsRead(): bool
    {
        return $this->update([
            'status' => self::STATUS_READ,
            'read_at' => $this->read_at ?? Carbon::now(),
        ]);
    }

    public function markAsReplied(): bool
    {
        return $this->update([
            'status' => self::STATUS_REPLIED,
            'read_at' => $this->read_at ?? Carbon::now(),
            'replied_at' => Carbon::now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'status' => self::STATUS_UNREAD,
            'read_at' => null,
            'replied_at' => null,
        ]);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_READ);
    }

    public function scopeReplied(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REPLIED);
    }
}
