<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTrialRequestNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     */
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
        $tr = $this->trialRequest;

        return (new MailMessage)
            ->subject('[Feeto] Nueva solicitud de prueba — '.$tr->business_name)
            ->greeting('¡Nuevo taller interesado!')
            ->line('Se ha registrado una nueva solicitud de prueba gratuita en la plataforma.')
            ->line("**Negocio:** {$tr->business_name}")
            ->line("**Rubro:** {$tr->business_type}")
            ->line("**Contacto:** {$tr->name}")
            ->line("**Correo:** {$tr->email}")
            ->line("**Teléfono:** {$tr->phone}")
            ->line('**Ciudad:** '.($tr->city ?: 'No indicada'))
            ->line('**Plan solicitado:** '.($tr->requested_plan ?: 'No especificado'))
            ->line('**Estimación de usuarios:** '.($tr->users_estimate ?? 'No indicada'))
            ->when(filled($tr->message), fn (MailMessage $mail) => $mail->line("**Mensaje:** {$tr->message}"))
            ->action('Ver solicitudes de prueba', route('admin.trial-requests.index'))
            ->line('Recuerda revisar la solicitud para aprobarla o rechazarla.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'trial_request_id' => $this->trialRequest->id,
            'business_name' => $this->trialRequest->business_name,
            'email' => $this->trialRequest->email,
        ];
    }
}
