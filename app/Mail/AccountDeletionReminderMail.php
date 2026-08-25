<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $permanentDeleteAt;

    public function __construct($user, $permanentDeleteAt)
    {
        $this->user = $user;
        $this->permanentDeleteAt = $permanentDeleteAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Your account will be permanently deleted in 3 days',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_deletion_reminder',
        );
    }
}
