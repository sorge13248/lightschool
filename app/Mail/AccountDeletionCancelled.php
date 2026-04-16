<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-deletion-cancelled-subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-deletion-cancelled-heading'),
                'body'    => __('email-deletion-cancelled-body', ['app' => config('app.name')]),
                'ctaText' => null,
                'ctaUrl'  => null,
            ],
        );
    }
}
