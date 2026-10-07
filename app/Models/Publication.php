<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'publication_id',
        'creator_id',
        'industry_id',
        'network_type',
        'publication_type',
        'title',
        'slug',
        'authors',
        'abstract',
        'website_url',
        'attachment',
        'information_confirmed',
        'status',
        'views_count',
        'likes_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Publication $publication) {
            if (empty($publication->publication_id)) {
                $seedId = (int) ($publication->industry_id ?: ($publication->creator_id ?: 1));
                $publication->publication_id = static::generateUniquePublicationId($seedId);
            }
        });
    }

    /**
     * Generate an scoped unique publication ID without database looping.
     * Incorporates seedId as prefix with dynamic suffix length.
     */
    public static function generateUniquePublicationId(int $seedId): string
    {
        $prefix = 'PB'.min($seedId, 9999);
        $randomLength = max(4, 9 - strlen($prefix));
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charLength = strlen($characters);

        do {
            $suffix = '';
            for ($i = 0; $i < $randomLength; $i++) {
                $suffix .= $characters[random_int(0, $charLength - 1)];
            }
            $candidate = $prefix.$suffix;
        } while (static::where('publication_id', $candidate)->exists());

        return $candidate;
    }

    protected $casts = [
        'authors' => 'array',
        'information_confirmed' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected function attachmentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attachment ? asset('storage/'.$this->attachment) : null,
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(PublicationView::class, 'publication_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PublicationLike::class, 'publication_id');
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
