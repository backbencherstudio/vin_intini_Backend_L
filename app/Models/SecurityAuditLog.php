<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAuditLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'severity',
        'ip_address',
        'user_agent',
        'token_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable()
    {
        return static::where(function ($query): void {
            $query->where('severity', 'info')
                ->where('created_at', '<=', now()->subDays(60));
        })->orWhere(function ($query): void {
            $query->where('severity', '!=', 'info')
                ->where('created_at', '<=', now()->subYear());
        });
    }
}
