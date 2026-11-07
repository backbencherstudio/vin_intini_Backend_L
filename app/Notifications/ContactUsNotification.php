<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ContactUsNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public mixed $contactData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subjectTitle = $this->contactData->subject ?? 'New Contact Inquiry from Website';

        return (new MailMessage)
            ->subject($subjectTitle)
            ->view('emails.contact_us_notification_mail', [
                'contactData' => $this->contactData,
            ]);
    }
}
