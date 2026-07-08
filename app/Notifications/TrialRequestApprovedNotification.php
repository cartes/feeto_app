<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TrialRequest $trialRequest,
        public readonly Tenant $tenant,
        public readonly string $temporaryPassword,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trialEndsAt = $this->tenant->subscription_ends_at?->format('d/m/Y') ?? '—';

        return (new MailMessage)
            ->subject("[Feeto] ¡Tu prueba gratuita de {$this->tenant->name} fue aprobada!")
            ->greeting("¡Hola, {$this->trialRequest->name}!")
            ->line("Tu solicitud de prueba gratuita para **{$this->tenant->name}** fue aprobada.")
            ->line('Ya puedes ingresar a la plataforma con estos datos de acceso:')
            ->line("**Correo:** {$this->trialRequest->email}")
            ->line("**Contraseña provisional:** {$this->temporaryPassword}")
            ->line("Tu prueba gratuita estará activa hasta el **{$trialEndsAt}** (14 días).")
            ->action('Ingresar a mi cuenta', route('login'))
            ->line('Te recomendamos cambiar tu contraseña provisional una vez que ingreses.')
            ->line('Si tienes dudas, responde directamente a este correo.')
            ->salutation('El equipo de Feeto');
    }
}
