<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class LoginActivity extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id',
        'token_id',
        'device',
        'browser',
        'ip_address',
        'location',
        'status',
        'is_active',
        'is_resolved',
        'is_trusted',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prunable()
    {
        // Prune login activities that are inactive and older than 3 months
        return static::where('is_active', false)->where('updated_at', '<=', now()->subMonths(3));
    }
}
