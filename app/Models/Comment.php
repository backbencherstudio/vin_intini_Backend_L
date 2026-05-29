<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'comment',
        'image',
        'like_count',
        'reply_count',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    protected static function booted()
    {
        static::deleting(function ($comment) {
            if ($comment->image) {
                Storage::disk('public')->delete($comment->image);
            }

            $replies = $comment->relationLoaded('replies')
                ? $comment->replies
                : $comment->replies()->get();

            $replies->each(function ($reply) {
                $reply->delete();
            });
        });
    }
}
