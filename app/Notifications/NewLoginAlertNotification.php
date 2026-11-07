<?php

namespace App\Notifications;

use App\Models\LoginActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Stevebauman\Location\Facades\Location;

class NewLoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $localTime;

    public string $userTimezone;

    public function __construct(
        public LoginActivity $activity,
        public string $trustUrl,
        public string $blockUrl,
    ) {
        $position = Location::get($activity->ip_address);
        $this->userTimezone = $position ? $position->timezone : 'UTC';
        $this->localTime = $activity->login_at
            ->timezone($this->userTimezone)
            ->format('d M Y, h:i A');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Security Alert: New Login Detected')
            ->view('emails.new_login_alert', [
                'activity' => $this->activity,
                'trustUrl' => $this->trustUrl,
                'blockUrl' => $this->blockUrl,
                'localTime' => $this->localTime,
                'userTimezone' => $this->userTimezone,
            ]);
    }
}
