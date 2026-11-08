<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use App\Services\Advertising\BoostSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CampaignAutoExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $maxPauseDays;

    public function __construct(
        public AdCampaign $campaign,
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
        return (new MailMessage)
            ->subject('Your paused boost campaign has expired - Mind Unite')
            ->view('emails.campaignAutoExpiredMail', [
                'campaign' => $this->campaign,
                'maxPauseDays' => $this->maxPauseDays,
            ]);
    }
}
