<?php

namespace App\Mail;

use App\Models\AdCampaign;
use App\Services\BoostSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignActivatedMail extends Mailable implements ShouldQueue
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
        $productName = $this->campaign->boostable?->product_name
            ?? $this->campaign->boostable?->name
            ?? 'Product';

        return $this->subject("Your Boost Campaign is Live: {$productName} - Mind Unite")
            ->view('emails.campaignActivatedMail', [
                'maxPauseDays' => $this->maxPauseDays,
            ]);
    }
}
