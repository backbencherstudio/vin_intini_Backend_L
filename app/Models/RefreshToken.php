<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'login_activity_id',
        'family_id',
        'token_hash',
        'jti',
        'expires_at',
        'rotated_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loginActivity(): BelongsTo
    {
        return $this->belongsTo(LoginActivity::class);
    }

    public function isUsable(): bool
    {
        return is_null($this->revoked_at)
            && is_null($this->rotated_at)
            && $this->expires_at->isFuture();
    }

    public function prunable()
    {
        return static::where('expires_at', '<=', now()->subDays(7))
            ->orWhere(function ($query): void {
                $query->whereNotNull('rotated_at')
                    ->where('rotated_at', '<=', now()->subDays(14));
            })
            ->orWhere(function ($query): void {
                $query->whereNotNull('revoked_at')
                    ->where('revoked_at', '<=', now()->subDays(14));
            });
    }
}
