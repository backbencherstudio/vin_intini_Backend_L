<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use App\Services\BoostSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CampaignActivatedNotification extends Notification implements ShouldQueue
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
        $productName = $this->campaign->boostable?->product_name
            ?? $this->campaign->boostable?->name
            ?? 'Product';

        return (new MailMessage)
            ->subject("Your Boost Campaign is Live: {$productName} - Mind Unite")
            ->view('emails.campaignActivatedMail', [
                'campaign' => $this->campaign,
                'maxPauseDays' => $this->maxPauseDays,
            ]);
    }
}
