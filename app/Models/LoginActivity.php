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
        'login_at'
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
        // 3 months before, we can prune the login activities
        return static::where('created_at', '<=', now()->subMonths(3));
    }
}
