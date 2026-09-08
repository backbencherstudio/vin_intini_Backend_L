<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryPostComment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'comment',
        'image',
        'likes_count',
    ];

    public function post()
    {
        return $this->belongsTo(
            IndustryPost::class,
            'post_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function parent()
    {
        return $this->belongsTo(
            IndustryPostComment::class,
            'parent_id'
        );
    }

    public function replies()
    {
        return $this->hasMany(
            IndustryPostComment::class,
            'parent_id'
        )
            ->latest();
    }

    public function likes()
    {
        return $this->hasMany(
            IndustryCommentLike::class,
            'comment_id'
        );
    }
}
