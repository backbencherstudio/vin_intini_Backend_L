<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryFollow extends Model
{
    protected $fillable = [
        'industry_id',
        'user_id'
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
