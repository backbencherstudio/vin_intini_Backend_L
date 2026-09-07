<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryPost extends Model
{
    protected $fillable = [
        'industry_id',
        'created_by',
        'content',
        'likes_count',
        'comments_count',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(IndustryPostMedia::class)
            ->orderBy('sort_order');
    }

    public function likes()
    {
        return $this->hasMany(
            IndustryPostLike::class,
            'post_id'
        );
    }

    public function comments()
    {
        return $this->hasMany(
            IndustryPostComment::class,
            'post_id'
        );
    }
}
