<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $verifyUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-verify-subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-verify-heading'),
                'body'    => __('email-verify-body'),
                'ctaText' => __('email-verify-cta'),
                'ctaUrl'  => $this->verifyUrl,
            ],
        );
    }
}
