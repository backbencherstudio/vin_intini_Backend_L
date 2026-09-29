<?php

namespace App\Models;

use App\Enums\OtpType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'otp',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OtpType::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at ? now()->greaterThan($this->expires_at) : true;
    }

    public function isValid(string|int $otp): bool
    {
        return (string) $this->otp === (string) $otp && ! $this->isExpired();
    }

    public function scopeForType(Builder $query, OtpType|string $type): Builder
    {
        $value = $type instanceof OtpType ? $type->value : $type;

        return $query->where('type', $value);
    }
}
