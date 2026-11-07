<?php

namespace App\Models;

use App\Enums\PlanFeature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'platform',
        'provider_subscription_id',
        'provider_customer_id',
        'product_id',
        'price_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
        'ends_at',
        'store',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'canceled_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isActive(int $gracePeriodDays = 0): bool
    {
        if (! in_array($this->status, ['active', 'trialing'], true)) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->current_period_end !== null) {
            $threshold = $gracePeriodDays > 0
                ? $this->current_period_end->copy()->addDays($gracePeriodDays)
                : $this->current_period_end;

            if ($threshold->isPast()) {
                return false;
            }
        }

        return true;
    }

    public function allows(PlanFeature $feature): bool
    {
        return in_array($feature->value, $this->plan?->features ?? [], true);
    }

    public function allowsCompanyProfile(): bool
    {
        return $this->allows(PlanFeature::COMPANY_PROFILE);
    }
}
