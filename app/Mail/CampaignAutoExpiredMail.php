<?php

namespace App\Mail;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignAutoExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdCampaign $campaign,
    ) {}

    public function build(): self
    {
        return $this->subject('Your paused boost campaign has expired - Mind Unite')
            ->view('emails.campaignAutoExpiredMail');
    }
}
