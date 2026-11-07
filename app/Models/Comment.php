<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'industry_id',
        'comment',
        'image',
        'like_count',
        'reply_count',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function isCompanyComment(): bool
    {
        return $this->industry_id !== null;
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    protected static function booted()
    {
        static::deleting(function ($comment): void {
            if ($comment->image) {
                Storage::disk('public')->delete($comment->image);
            }

            $replies = $comment->relationLoaded('replies')
                ? $comment->replies
                : $comment->replies()->get();

            $replies->each(function ($reply): void {
                $reply->delete();
            });
        });
    }
}
