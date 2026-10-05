<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryProductView extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_product_id',
        'user_id',
        'ip_address',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(IndustryProduct::class, 'industry_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
