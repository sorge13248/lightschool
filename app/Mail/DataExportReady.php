<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataExportReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $downloadUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email-data-export-ready-subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.base',
            with: [
                'heading' => __('email-data-export-ready-heading'),
                'body'    => __('email-data-export-ready-body', ['app' => config('app.name')]),
                'ctaText' => __('email-data-export-ready-cta'),
                'ctaUrl'  => $this->downloadUrl,
            ],
        );
    }
}
