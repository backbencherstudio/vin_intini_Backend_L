<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'visibility',
        'who_can_comment',
        'total_like',
        'total_comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'post_groups');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    protected static function booted()
    {
        static::deleting(function ($post) {
            if ($post->media) {
                foreach ($post->media as $item) {
                    if ($item->file_path) {
                        Storage::disk('public')->delete($item->file_path);
                    }
                    $item->delete();
                }
            }

            $post->comments()->get()->each(function ($comment) {
                $comment->delete();
            });
        });
    }
}
