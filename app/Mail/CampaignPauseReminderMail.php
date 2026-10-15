<?php

namespace App\Mail;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignPauseReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdCampaign $campaign,
        public int $daysRemaining,
    ) {}

    public function build(): self
    {
        $label = $this->daysRemaining === 1 ? 'day' : 'days';

        return $this->subject("Action Required: Your paused boost campaign expires in {$this->daysRemaining} {$label} - Mind Unite")
            ->view('emails.campaignPauseReminderMail');
    }
}
