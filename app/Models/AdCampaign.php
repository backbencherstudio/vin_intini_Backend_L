<?php

namespace App\Models;

use App\Enums\AdCampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'boostable_type',
        'boostable_id',
        'user_id',
        'industry_id',
        'boost_package_id',
        'ad_type',
        'network_type',
        'placements',
        'amount_paid',
        'currency',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'revenuecat_transaction_id',
        'payment_platform',
        'duration_days',
        'starts_at',
        'ends_at',
        'paused_at',
        'paused_reminder_stage',
        'status',
        'total_impressions',
        'total_clicks',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => 'pending',
        'total_impressions' => 0,
        'total_clicks' => 0,
        'paused_reminder_stage' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => AdCampaignStatus::class,
            'placements' => 'array',
            'amount_paid' => 'decimal:2',
            'duration_days' => 'integer',
            'total_impressions' => 'integer',
            'total_clicks' => 'integer',
            'paused_reminder_stage' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }

    public function boostable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function boostPackage(): BelongsTo
    {
        return $this->belongsTo(BoostPackage::class);
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class);
    }

    public function isActive(): bool
    {
        return $this->status === AdCampaignStatus::ACTIVE
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    public function isPaused(): bool
    {
        return $this->status === AdCampaignStatus::PAUSED;
    }

    /**
     * Pause the campaign and freeze the remaining time.
     */
    public function pause(): bool
    {
        if ($this->status !== AdCampaignStatus::ACTIVE) {
            return false;
        }

        $this->update([
            'status' => AdCampaignStatus::PAUSED,
            'paused_at' => now(),
            'paused_reminder_stage' => 0,
        ]);

        if ($this->boostable && method_exists($this->boostable, 'update')) {
            $this->boostable->update(['is_boosted' => false]);
        }

        return true;
    }

    /**
     * Resume the campaign and extend the end date by the exact paused duration.
     */
    public function resume(): bool
    {
        if ($this->status !== AdCampaignStatus::PAUSED || ! $this->paused_at) {
            return false;
        }

        $pausedSeconds = (int) abs(now()->diffInSeconds($this->paused_at));
        $newEndsAt = $this->ends_at ? $this->ends_at->copy()->addSeconds($pausedSeconds) : now()->addDays($this->duration_days);

        $this->update([
            'status' => AdCampaignStatus::ACTIVE,
            'ends_at' => $newEndsAt,
            'paused_at' => null,
            'paused_reminder_stage' => 0,
        ]);

        if ($this->boostable && method_exists($this->boostable, 'update')) {
            $this->boostable->update([
                'is_boosted' => true,
                'boost_expires_at' => $newEndsAt,
            ]);
        }

        return true;
    }
}
