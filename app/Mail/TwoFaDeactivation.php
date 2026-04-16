<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFaDeactivation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $deactivateUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-otp-subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-otp-heading'),
                'body'    => __('email-otp-body'),
                'ctaText' => __('email-otp-cta'),
                'ctaUrl'  => $this->deactivateUrl,
            ],
        );
    }
}
