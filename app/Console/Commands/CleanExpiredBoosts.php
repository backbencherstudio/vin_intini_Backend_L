<?php

namespace App\Console\Commands;

use App\Enums\AdCampaignStatus;
use App\Mail\CampaignAutoExpiredMail;
use App\Mail\CampaignPauseReminderMail;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;
use App\Services\BoostSettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CleanExpiredBoosts extends Command
{
    protected $signature = 'boost:clean-expired';

    protected $description = 'Clean expired ad boost campaigns, enforce 30-day pause policy, and clear free new releases';

    public function handle(): int
    {
        $now = now();

        // 1. Mark expired active campaigns as completed
        $expiredCampaigns = AdCampaign::where('status', AdCampaignStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();

        $completedCampaignsCount = 0;
        foreach ($expiredCampaigns as $campaign) {
            $campaign->update([
                'status' => AdCampaignStatus::COMPLETED,
            ]);
            $completedCampaignsCount++;

            // If the boostable target has no other active campaigns, remove boosted flag
            if ($campaign->boostable && method_exists($campaign->boostable, 'update')) {
                $hasOtherActive = AdCampaign::where('boostable_type', $campaign->boostable_type)
                    ->where('boostable_id', $campaign->boostable_id)
                    ->where('status', AdCampaignStatus::ACTIVE)
                    ->where('ends_at', '>', $now)
                    ->exists();

                if (! $hasOtherActive) {
                    $campaign->boostable->update([
                        'is_boosted' => false,
                    ]);
                }
            }
        }

        // 2. Clear expired free new release perks on products
        $expiredNewReleasesCount = IndustryProduct::where('is_new_release', true)
            ->whereNotNull('new_release_expires_at')
            ->where('new_release_expires_at', '<=', $now)
            ->update([
                'is_new_release' => false,
            ]);

        // 3. Enforce 30-Day Enterprise Pause Policy on Paused Campaigns
        $pausePolicy = app(BoostSettingService::class)->getPausePolicy();
        $maxPauseDays = (int) ($pausePolicy['max_days'] ?? 30);
        $firstReminderDays = (int) ($pausePolicy['first_reminder_days'] ?? 27);
        $finalReminderDays = (int) ($pausePolicy['final_reminder_days'] ?? 29);

        $pausedCampaigns = AdCampaign::where('status', AdCampaignStatus::PAUSED)
            ->whereNotNull('paused_at')
            ->with(['user', 'boostable'])
            ->get();

        $autoExpiredPausedCount = 0;
        $remindersSentCount = 0;

        foreach ($pausedCampaigns as $campaign) {
            $pausedDays = (int) $campaign->paused_at->diffInDays($now);

            // Auto-expire when reaching or exceeding max allowed pause duration (30 days)
            if ($pausedDays >= $maxPauseDays) {
                $campaign->update([
                    'status' => AdCampaignStatus::COMPLETED,
                    'paused_at' => null,
                ]);

                if ($campaign->user?->email) {
                    Mail::to($campaign->user->email)->queue(new CampaignAutoExpiredMail($campaign, $maxPauseDays));
                }

                $autoExpiredPausedCount++;

                continue;
            }

            // Final 24-hour reminder (Day 29)
            if ($pausedDays >= $finalReminderDays && $campaign->paused_reminder_stage < 2) {
                $campaign->update(['paused_reminder_stage' => 2]);

                if ($campaign->user?->email) {
                    $daysRemaining = max(1, $maxPauseDays - $pausedDays);
                    Mail::to($campaign->user->email)->queue(new CampaignPauseReminderMail($campaign, $daysRemaining, $maxPauseDays));
                }

                $remindersSentCount++;

                continue;
            }

            // First 3-day reminder (Day 27)
            if ($pausedDays >= $firstReminderDays && $campaign->paused_reminder_stage < 1) {
                $campaign->update(['paused_reminder_stage' => 1]);

                if ($campaign->user?->email) {
                    $daysRemaining = max(1, $maxPauseDays - $pausedDays);
                    Mail::to($campaign->user->email)->queue(new CampaignPauseReminderMail($campaign, $daysRemaining, $maxPauseDays));
                }

                $remindersSentCount++;
            }
        }

        $this->info("Expired active boost campaigns completed: {$completedCampaignsCount}");
        $this->info("Expired free new releases cleared: {$expiredNewReleasesCount}");
        $this->info("Paused campaigns auto-expired (exceeded 30 days): {$autoExpiredPausedCount}");
        $this->info("Pause policy reminder emails sent: {$remindersSentCount}");

        return self::SUCCESS;
    }
}
