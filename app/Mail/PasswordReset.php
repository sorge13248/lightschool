<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-password-subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-password-heading'),
                'body'    => __('email-password-body'),
                'ctaText' => __('email-password-cta'),
                'ctaUrl'  => $this->resetUrl,
            ],
        );
    }
}
