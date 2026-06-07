<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactUsNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $contactData;

    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    public function envelope(): Envelope
    {
        $subjectTitle = $this->contactData->subject ?? 'New Contact Inquiry from Website';

        return new Envelope(
            subject: $subjectTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_us_notification_mail',
        );
    }
}
