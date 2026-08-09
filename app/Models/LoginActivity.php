<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'device',
        'browser',
        'ip_address',
        'location',
        'status',
        'is_active',
        'login_at'
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];
}
