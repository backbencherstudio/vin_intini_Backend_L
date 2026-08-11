<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\LoginActivity;

class NewLoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $activity;
    public $trustUrl;
    public $blockUrl;

    public function __construct(LoginActivity $activity, $trustUrl, $blockUrl)
    {
        $this->activity = $activity;
        $this->trustUrl = $trustUrl;
        $this->blockUrl = $blockUrl;
    }

    public function build()
    {
        return $this->subject('Security Alert: New Login Detected')
            ->view('emails.new_login_alert');
    }
}
