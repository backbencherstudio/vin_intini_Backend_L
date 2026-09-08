<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryPostMedia extends Model
{
    protected $fillable = [
        'industry_post_id',
        'type',
        'path',
        'sort_order',
    ];

    public function industryPost(): BelongsTo
    {
        return $this->belongsTo(
            IndustryPost::class,
            'industry_post_id'
        );
    }
}
