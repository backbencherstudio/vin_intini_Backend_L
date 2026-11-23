<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BoostOtpNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int|string $otp,
        public ?string $targetTitle = null,
        public string $targetType = 'product',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = ! empty($this->targetTitle)
            ? "Confirm boost campaign for \"{$this->targetTitle}\" - Mind Unite"
            : 'Confirm your boost campaign - Mind Unite';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.boostOtpMail', [
                'otp' => $this->otp,
                'targetTitle' => $this->targetTitle,
                'targetType' => $this->targetType,
            ]);
    }
}
