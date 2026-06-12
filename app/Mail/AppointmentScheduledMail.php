<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentScheduledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Crea una nueva instancia de mensaje.
     */
    public function __construct(public Appointment $appointment) {}

    /**
     * Obtiene el sobre del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Agenda] Nueva cita agendada - Taller Flow',
        );
    }

    /**
     * Obtiene la definición del contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointment-scheduled',
        );
    }

    /**
     * Obtiene los archivos adjuntos para el mensaje.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
