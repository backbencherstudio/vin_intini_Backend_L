<?php

namespace App\Mail;

use App\Models\AdCampaign;
use App\Services\BoostSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignAutoExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $maxPauseDays;

    public function __construct(
        public AdCampaign $campaign,
        ?int $maxPauseDays = null,
    ) {
        $this->maxPauseDays = $maxPauseDays ?? (int) (app(BoostSettingService::class)->getPausePolicy()['max_days'] ?? 30);
    }

    public function build(): self
    {
        return $this->subject('Your paused boost campaign has expired - Mind Unite')
            ->view('emails.campaignAutoExpiredMail', [
                'maxPauseDays' => $this->maxPauseDays,
            ]);
    }
}
