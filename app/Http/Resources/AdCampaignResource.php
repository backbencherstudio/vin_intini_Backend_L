<?php

namespace App\Http\Resources;

use App\Enums\AdCampaignStatus;
use App\Models\IndustryProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = now();
        $totalImpressions = (int) $this->total_impressions;
        $totalClicks = (int) $this->total_clicks;
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;
        $amountPaid = (float) $this->amount_paid;

        // Status flags
        $statusValue = $this->status instanceof AdCampaignStatus ? $this->status->value : (string) $this->status;
        $isPaused = $statusValue === AdCampaignStatus::PAUSED->value;
        $isActive = $statusValue === AdCampaignStatus::ACTIVE->value;
        $isCompleted = $statusValue === AdCampaignStatus::COMPLETED->value;

        // Timeline & Duration calculation
        $totalSeconds = $this->starts_at && $this->ends_at
            ? max(1, (int) abs($this->starts_at->diffInSeconds($this->ends_at)))
            : max(1, (int) ($this->duration_days ?: 1) * 86400);

        if ($isPaused && $this->paused_at && $this->ends_at) {
            $remainingSeconds = max(0, (int) $this->paused_at->diffInSeconds($this->ends_at, false));
        } elseif ($this->ends_at && $this->ends_at->isFuture()) {
            $remainingSeconds = max(0, (int) $now->diffInSeconds($this->ends_at, false));
        } else {
            $remainingSeconds = 0;
        }

        $remainingDays = (int) ceil($remainingSeconds / 86400);

        if ($isCompleted) {
            $progressPercentage = 100.0;
        } elseif ($this->starts_at && $totalSeconds > 0) {
            $elapsedSeconds = $isPaused && $this->paused_at
                ? max(0, (int) $this->starts_at->diffInSeconds($this->paused_at))
                : max(0, (int) $this->starts_at->diffInSeconds($now));
            $progressPercentage = min(100.0, round(($elapsedSeconds / $totalSeconds) * 100, 1));
        } else {
            $progressPercentage = 0.0;
        }

        // 30-day max freeze policy tracking
        $maxPauseDays = 30;
        $pausedDaysUsed = ($isPaused && $this->paused_at) ? (int) $this->paused_at->diffInDays($now) : 0;
        $freezeDaysRemaining = $isPaused ? max(0, $maxPauseDays - $pausedDaysUsed) : null;

        return [
            'id' => $this->id,
            'boostable_type' => $this->boostable_type,
            'boostable_id' => $this->boostable_id,
            'user_id' => $this->user_id,
            'industry_id' => $this->industry_id,
            'boost_package_id' => $this->boost_package_id,
            'ad_type' => $this->ad_type,
            'network_type' => $this->network_type,
            'placements' => $this->placements,
            'amount_paid' => $amountPaid,
            'amount_paid_formatted' => '$'.number_format($amountPaid, 2),
            'currency' => $this->currency ?? 'USD',
            'duration_days' => (int) $this->duration_days,
            'status' => $statusValue,
            'is_active' => $isActive,
            'is_paused' => $isPaused,
            'is_completed' => $isCompleted,
            'can_pause' => $isActive,
            'can_resume' => $isPaused,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'paused_at' => $this->paused_at?->toIso8601String(),
            'starts_at_formatted' => $this->starts_at?->format('M d, Y'),
            'ends_at_formatted' => $this->ends_at?->format('M d, Y'),
            'remaining_days' => $remainingDays,
            'remaining_seconds' => $remainingSeconds,
            'progress_percentage' => $progressPercentage,
            'freeze_days_remaining' => $freezeDaysRemaining,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,
            'ctr_formatted' => $ctr.'%',
            'boost_package' => $this->whenLoaded('boostPackage', function () {
                return [
                    'id' => $this->boostPackage->id,
                    'name' => $this->boostPackage->name,
                    'badge' => $this->boostPackage->badge,
                    'duration_days' => (int) $this->boostPackage->duration_days,
                    'price' => (float) $this->boostPackage->price,
                ];
            }),
            'boostable' => $this->whenLoaded('boostable', function () {
                if ($this->boostable instanceof IndustryProduct) {
                    return [
                        'id' => $this->boostable->id,
                        'product_name' => $this->boostable->product_name,
                        'slug' => $this->boostable->slug,
                        'image_url' => $this->boostable->image_url,
                        'network_type' => $this->boostable->network_type,
                        'industry_type' => $this->boostable->industry_type,
                        'status' => $this->boostable->status,
                    ];
                }

                return [
                    'id' => $this->boostable?->id,
                    'name' => $this->boostable?->name ?? $this->boostable?->title ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
