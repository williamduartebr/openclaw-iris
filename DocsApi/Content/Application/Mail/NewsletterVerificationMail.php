<?php

namespace Src\Content\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Content\Domain\Models\NewsletterSubscriber;

class NewsletterVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirme sua inscrição — Mercado Veículos',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'content::mail.newsletter-verification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
