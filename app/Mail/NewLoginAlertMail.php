<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\LoginActivity;
use Stevebauman\Location\Facades\Location; 

class NewLoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $activity;
    public $trustUrl;
    public $blockUrl;
    public $localTime;
    public $userTimezone;

    public function __construct(LoginActivity $activity, $trustUrl, $blockUrl)
    {
        $this->activity = $activity;
        $this->trustUrl = $trustUrl;
        $this->blockUrl = $blockUrl;

        $position = Location::get($activity->ip_address);

        $this->userTimezone = $position ? $position->timezone : 'UTC';

        $this->localTime = $activity->login_at
            ->timezone($this->userTimezone)
            ->format('d M Y, h:i A');
    }

    public function build()
    {
        return $this->subject('Security Alert: New Login Detected')
            ->view('emails.new_login_alert');
    }
}
