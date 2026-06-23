<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConnectionRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $sender;
    public $receiver;
    public $connectionData;

    public function __construct($sender, $receiver, $connectionData)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->connectionData = $connectionData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Connection Request from ' . $this->sender->first_name . ' ' . $this->sender->last_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.connection_request_mail',
        );
    }
}
