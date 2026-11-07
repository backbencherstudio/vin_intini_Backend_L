<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Reply extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'comment_id',
        'user_id',
        'industry_id',
        'reply',
        'like_count',
        'image',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function isCompanyReply(): bool
    {
        return $this->industry_id !== null;
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReplyLike::class);
    }

    protected static function booted(): void
    {
        static::deleting(function ($reply): void {
            if ($reply->image) {
                Storage::disk('public')->delete($reply->image);
            }
        });
    }
}
