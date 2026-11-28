<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'token_id',
        'device_token_hash',
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

    protected $hidden = [
        'device_token_hash',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'is_active' => 'boolean',
        'is_resolved' => 'boolean',
        'is_trusted' => 'boolean',
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
