<?php

namespace App\Models;

use App\Enums\AdCampaignStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string|null $attachment_url
 * @property-read string|null $file_extension
 */
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
        'is_new_release',
        'new_release_expires_at',
        'is_boosted',
        'boosted_at',
        'boost_expires_at',
        'boost_score',
        'impressions_count',
        'clicks_count',
        'views_count',
        'likes_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Publication $publication): void {
            if (empty($publication->publication_id)) {
                $seedId = (int) ($publication->industry_id ?: ($publication->creator_id ?: 1));
                $publication->publication_id = static::generateUniquePublicationId($seedId);
            }

            if (! isset($publication->attributes['is_new_release']) || $publication->is_new_release === null) {
                try {
                    $newReleaseHours = app(\App\Services\Advertising\BoostSettingService::class)->getNewReleaseHours();
                } catch (\Throwable) {
                    $newReleaseHours = (int) config('boosting.new_release_hours', 24);
                }
                if (($publication->status ?? 'active') === 'active' && $newReleaseHours > 0) {
                    $publication->is_new_release = true;
                    $publication->new_release_expires_at = now()->addHours($newReleaseHours);
                }
            }
        });

        static::updating(function (Publication $publication): void {
            if ($publication->isDirty('status') && $publication->status === 'active' && empty($publication->new_release_expires_at)) {
                try {
                    $newReleaseHours = app(\App\Services\Advertising\BoostSettingService::class)->getNewReleaseHours();
                } catch (\Throwable) {
                    $newReleaseHours = (int) config('boosting.new_release_hours', 24);
                }
                if ($newReleaseHours > 0) {
                    $publication->is_new_release = true;
                    $publication->new_release_expires_at = now()->addHours($newReleaseHours);
                }
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
        'is_new_release' => 'boolean',
        'new_release_expires_at' => 'datetime',
        'is_boosted' => 'boolean',
        'boosted_at' => 'datetime',
        'boost_expires_at' => 'datetime',
        'boost_score' => 'integer',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected $appends = [
        'attachment_url',
        'file_extension',
    ];

    protected function attachmentUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attachment ? asset('storage/'.$this->attachment) : null,
        );
    }

    protected function fileExtension(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (empty($this->attachment)) {
                    return null;
                }

                $path = parse_url((string) $this->attachment, PHP_URL_PATH) ?? (string) $this->attachment;
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                return ! empty($extension) ? strtolower($extension) : null;
            },
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

    public function adCampaigns(): MorphMany
    {
        return $this->morphMany(AdCampaign::class, 'boostable');
    }

    public function campaigns(): MorphMany
    {
        return $this->morphMany(AdCampaign::class, 'boostable');
    }

    public function activeCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->where('status', AdCampaignStatus::ACTIVE)
            ->where('ends_at', '>', now());
    }

    public function latestCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->latestOfMany();
    }

    public function getBoostStatus(): string
    {
        if ($this->isBoosted()) {
            return 'active';
        }

        $campaign = $this->relationLoaded('latestCampaign')
            ? $this->latestCampaign
            : ($this->relationLoaded('activeCampaign') && $this->activeCampaign ? $this->activeCampaign : $this->campaigns()->latest('id')->first());

        if (! $campaign) {
            return 'inactive';
        }

        $status = $campaign->status instanceof AdCampaignStatus
            ? $campaign->status->value
            : (string) $campaign->status;

        if ($status === 'paused') {
            return 'paused';
        }

        if ($status === 'active' && ($campaign->ends_at === null || $campaign->ends_at->isFuture())) {
            return 'active';
        }

        if ($status === 'completed' || ($campaign->ends_at && $campaign->ends_at->isPast())) {
            return 'completed';
        }

        if ($status === 'cancelled') {
            return 'cancelled';
        }

        if ($status === 'pending') {
            return 'pending';
        }

        return 'inactive';
    }

    public function isBoosted(): bool
    {
        return (bool) $this->is_boosted && ($this->boost_expires_at === null || $this->boost_expires_at->isFuture());
    }

    public function isBoostActive(): bool
    {
        return $this->isBoosted();
    }

    public function isNewRelease(): bool
    {
        if (! $this->is_new_release) {
            return false;
        }

        if ($this->new_release_expires_at === null) {
            return true;
        }

        $expiresAt = $this->new_release_expires_at instanceof \Carbon\CarbonInterface
            ? $this->new_release_expires_at
            : \Illuminate\Support\Carbon::parse($this->new_release_expires_at);

        return $expiresAt->isFuture();
    }

    public function getAdBadge(): ?string
    {
        if ($this->isBoosted()) {
            return 'Sponsored';
        }

        if ($this->isNewRelease()) {
            return 'New Release';
        }

        return null;
    }

    public function scopeBoosted(Builder $query): Builder
    {
        return $query->where('is_boosted', true)
            ->where('boost_expires_at', '>', now());
    }

    public function scopeNewRelease(Builder $query): Builder
    {
        return $query->where('is_new_release', true)
            ->where('new_release_expires_at', '>', now());
    }

    public function scopeFeedOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_boosted')
            ->orderByDesc('is_new_release')
            ->orderByDesc('boost_score')
            ->latest('id');
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getAdTitle(): string
    {
        return $this->title ?? 'Publication';
    }

    public function getAdDescription(): ?string
    {
        return $this->abstract ?? null;
    }

    public function getAdImageUrl(): ?string
    {
        return $this->attachment_url;
    }

    public function getAdDestinationUrl(): ?string
    {
        if (! empty($this->website_url)) {
            return $this->website_url;
        }

        if (! empty($this->attachment_url)) {
            return $this->attachment_url;
        }

        return url("/mu/publications/{$this->id}");
    }
}
