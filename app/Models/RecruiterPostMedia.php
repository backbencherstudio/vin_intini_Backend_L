<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterPostMedia extends Model
{
    protected $fillable = [
        'recruiter_post_id',
        'type',
        'path',
        'sort_order',
    ];

    public function recruiterPost(): BelongsTo
    {
        return $this->belongsTo(
            RecruiterPost::class,
            'recruiter_post_id'
        );
    }
}
