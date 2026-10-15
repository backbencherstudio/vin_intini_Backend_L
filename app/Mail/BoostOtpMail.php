<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BoostOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int|string $otp,
    ) {}

    public function build(): self
    {
        return $this->subject('Confirm your boost campaign - Mind Unite')
            ->view('emails.boostOtpMail');
    }
}
