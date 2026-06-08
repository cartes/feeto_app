<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantContactMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(public array $data) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $isQuote = ($this->data['type'] ?? 'general') === 'quote';
        $subject = $isQuote
            ? '[Cotización] Nueva solicitud de cotización - Feeto'
            : '[Contacto] Nuevo mensaje de contacto - Feeto';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-contact',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
