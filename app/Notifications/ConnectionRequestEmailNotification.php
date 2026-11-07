<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ConnectionRequestEmailNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public mixed $sender,
        public mixed $receiver,
        public mixed $connectionData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Connection Request from '.$this->sender->first_name.' '.$this->sender->last_name)
            ->view('emails.connection_request_mail', [
                'sender' => $this->sender,
                'receiver' => $this->receiver,
                'connectionData' => $this->connectionData,
            ]);
    }
}
