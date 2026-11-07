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
use Illuminate\Support\Facades\Cache;

/**
 * @property-read string|null $image_url
 */
class IndustryProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'creator_id',
        'industry_id',
        'network_type',
        'industry_type',
        'section_id',
        'category_id',
        'product_name',
        'slug',
        'description',
        'product_url',
        'image',
        'tags',
        'poc_name',
        'poc_email',
        'poc_phone',
        'information_confirmed',
        'status',
        'is_new_release',
        'new_release_expires_at',
        'is_boosted',
        'boosted_at',
        'boost_expires_at',
        'boost_score',
        'views_count',
        'unique_views_count',
        'impressions_count',
        'clicks_count',
        'likes_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (IndustryProduct $product): void {
            if (empty($product->product_id)) {
                $product->product_id = static::generateUniqueProductId((int) ($product->industry_id ?: 1));
            }
        });
    }

    /**
     * Generate an industry-scoped unique product ID without database looping.
     * Incorporates industry_id as prefix with dynamic suffix length:
     * - 1 to 3 digit industry IDs produce a clean 6-character code (e.g. 47K9M2, 129B7Q, 1058XF)
     * - 4+ digit industry IDs scale cleanly with at least 3 random chars (e.g. 10008XF -> 7 chars)
     */
    public static function generateUniqueProductId(int $industryId): string
    {
        $prefix = (string) $industryId;
        $randomLength = max(3, 6 - strlen($prefix));
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charLength = strlen($characters);

        $suffix = '';
        for ($i = 0; $i < $randomLength; $i++) {
            $suffix .= $characters[random_int(0, $charLength - 1)];
        }

        return $prefix.$suffix;
    }

    protected $casts = [
        'tags' => 'array',
        'information_confirmed' => 'boolean',
        'is_new_release' => 'boolean',
        'new_release_expires_at' => 'datetime',
        'is_boosted' => 'boolean',
        'boosted_at' => 'datetime',
        'boost_expires_at' => 'datetime',
        'boost_score' => 'integer',
        'views_count' => 'integer',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->image) {
                    return null;
                }
                if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                    return $this->image;
                }

                return asset('storage/'.ltrim($this->image, '/'));
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(IndustrySections::class, 'section_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IndustryCategory::class, 'category_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(IndustryProductView::class, 'industry_product_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(IndustryProductLike::class, 'industry_product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function campaigns(): MorphMany
    {
        return $this->morphMany(AdCampaign::class, 'boostable');
    }

    public function activeCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->where('status', 'active')
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

    public function isNewRelease(): bool
    {
        return (bool) $this->is_new_release && ($this->new_release_expires_at === null || $this->new_release_expires_at->isFuture());
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

    public function scopeFeedOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_boosted')
            ->orderBy('impressions_count', 'asc')
            ->orderByDesc('is_new_release')
            ->orderByDesc('id');
    }

    public function getAdTitle(): string
    {
        return (string) $this->product_name;
    }

    public function getAdDescription(): ?string
    {
        return $this->description;
    }

    public function getAdImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function getAdDestinationUrl(): ?string
    {
        return $this->product_url;
    }

    public function getAdNetworkType(): string
    {
        return (string) $this->network_type;
    }

    public function getAdType(): string
    {
        return 'product';
    }

    /**
     * Check if a given user is the creator or industry owner of this product.
     */
    public function isOwnedBy(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ((int) $user->id === (int) $this->creator_id) {
            return true;
        }

        if ($this->industry_id && $user->industry && (int) $user->industry->id === (int) $this->industry_id) {
            return true;
        }

        return $this->industry && (int) $this->industry->created_by === (int) $user->id;
    }

    /**
     * Record a product view with unique visitor deduplication and 30-second cooldown protection.
     */
    public function recordView(?User $user = null, ?string $ip = null): void
    {
        // Anti-fraud self-view guard: Do not count view if user is the product creator or owner
        if ($this->isOwnedBy($user)) {
            return;
        }

        $viewCriteria = [
            'industry_product_id' => $this->id,
            'user_id' => $user?->id,
        ];

        if (! $user instanceof User) {
            $viewCriteria['ip_address'] = $ip;
        }

        $view = IndustryProductView::firstOrCreate(
            $viewCriteria,
            ['ip_address' => $ip]
        );

        if ($view->wasRecentlyCreated) {
            $this->increment('unique_views_count');
        }

        // Total views: increments on every view, protected by a 30s cache cooldown per user/IP
        $userIdentifier = $user instanceof User ? 'user_'.$user->id : 'ip_'.($ip ?: 'unknown');
        $cooldownKey = "product_view_throttle_{$this->id}_{$userIdentifier}";

        if (Cache::add($cooldownKey, true, now()->addSeconds(30))) {
            $this->increment('views_count');
        } elseif ($view->wasRecentlyCreated && $this->views_count == 0) {
            $this->increment('views_count');
        }
    }
}
