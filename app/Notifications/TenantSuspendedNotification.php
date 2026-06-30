<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantSuspendedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Tenant $tenant) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryDate = $this->tenant->subscription_ends_at?->format('d/m/Y') ?? 'fecha no registrada';

        return (new MailMessage)
            ->subject('[Feeto] Tu cuenta ha sido suspendida')
            ->greeting("Hola, {$this->tenant->name}")
            ->line('Tu suscripción a Feeto venció el **'.$expiryDate.'** y tu cuenta ha sido suspendida automáticamente.')
            ->line('Mientras tu cuenta esté suspendida, tus clientes no podrán acceder al portal y tus flujos de trabajo quedarán en pausa.')
            ->action('Renovar mi suscripción', route('checkout.index'))
            ->line('Si crees que esto es un error o necesitas ayuda, responde este correo o contáctanos directamente.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ];
    }
}
