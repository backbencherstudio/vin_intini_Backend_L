<?php

namespace App\Notifications;

use App\Models\LoginActivity;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Stevebauman\Location\Facades\Location;

class NewLoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public LoginActivity $activity,
        public string $trustUrl,
        public string $blockUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userTimezone = 'UTC';
        try {
            $position = Location::get($this->activity->ip_address);
            if ($position && ! empty($position->timezone)) {
                $userTimezone = $position->timezone;
            }
        } catch (\Throwable) {
            $userTimezone = 'UTC';
        }

        $loginAt = $this->activity->login_at
            ? Carbon::parse($this->activity->login_at)
            : now();
        $localTime = $loginAt->timezone($userTimezone)->format('d M Y, h:i A');

        return (new MailMessage)
            ->subject('Security Alert: New Login Detected')
            ->view('emails.new_login_alert', [
                'activity' => $this->activity,
                'trustUrl' => $this->trustUrl,
                'blockUrl' => $this->blockUrl,
                'localTime' => $localTime,
                'userTimezone' => $userTimezone,
            ]);
    }
}
