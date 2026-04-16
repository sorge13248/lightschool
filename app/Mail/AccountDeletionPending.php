<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionPending extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $deletionDate,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-deletion-pending-subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-deletion-pending-heading'),
                'body'    => __('email-deletion-pending-body', ['app' => config('app.name'), 'date' => $this->deletionDate]),
                'ctaText' => __('email-deletion-pending-cta'),
                'ctaUrl'  => $this->loginUrl,
            ],
        );
    }
}
