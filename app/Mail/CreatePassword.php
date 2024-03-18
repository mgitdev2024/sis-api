<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatePassword extends Mailable
{
    use Queueable, SerializesModels;

    public $full_name, $temporary_url;
    /**
     * Create a new message instance.
     */
    public function __construct($full_name, $temporary_url)
    {
        $this->$full_name = $full_name;
        $this->temporary_url = $temporary_url;
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Create Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.create-password-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            // 'logo' => public_path('img/logo.png'),
        ];
    }
}
