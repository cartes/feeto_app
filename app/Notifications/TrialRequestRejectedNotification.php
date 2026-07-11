<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly TrialRequest $trialRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Taller Flow] Novedades sobre tu solicitud de prueba — {$this->trialRequest->business_name}")
            ->greeting("Hola, {$this->trialRequest->name}")
            ->line("Gracias por tu interés en Taller Flow para **{$this->trialRequest->business_name}**.")
            ->line('Luego de revisar tu solicitud de prueba gratuita, no fue posible aprobarla en esta oportunidad.')
            ->when(filled($this->trialRequest->rejection_reason), fn (MailMessage $mail) => $mail->line("**Motivo:** {$this->trialRequest->rejection_reason}"))
            ->line('Si crees que esto es un error o quieres conversarlo, responde directamente a este correo.')
            ->salutation('El equipo de Taller Flow');
    }
}
