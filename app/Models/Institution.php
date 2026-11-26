<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_verified',
        'logo',
        'type',
        'state',
        'country',
        'website',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Resolve an institution from user-entered text without exposing unverified entries publicly.
     */
    public static function findOrCreateFromUserInput(string $rawName): self
    {
        $cleanName = preg_replace('/\s+/', ' ', trim($rawName)) ?? trim($rawName);

        $existing = static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'name' => $cleanName,
            'is_verified' => false,
        ]);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }
}
