<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use App\Services\BoostSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CampaignPauseReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $maxPauseDays;

    public function __construct(
        public AdCampaign $campaign,
        public int $daysRemaining,
        ?int $maxPauseDays = null,
    ) {
        $this->maxPauseDays = $maxPauseDays ?? (int) (app(BoostSettingService::class)->getPausePolicy()['max_days'] ?? 30);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->daysRemaining === 1 ? 'day' : 'days';

        return (new MailMessage)
            ->subject("Action Required: Your paused boost campaign expires in {$this->daysRemaining} {$label} - Mind Unite")
            ->view('emails.campaignPauseReminderMail', [
                'campaign' => $this->campaign,
                'daysRemaining' => $this->daysRemaining,
                'maxPauseDays' => $this->maxPauseDays,
            ]);
    }
}
