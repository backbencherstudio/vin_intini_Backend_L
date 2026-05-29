<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostIndustry extends Model
{
    protected $table = 'post_industry';

    protected $fillable = [
        'post_id',
        'industry_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
