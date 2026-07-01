<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminder extends Notification
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
        $daysLeft = (int) now()->diffInDays($this->tenant->subscription_ends_at);
        $expiryDate = $this->tenant->subscription_ends_at->format('d/m/Y');
        $checkoutUrl = route('checkout.show', $this->tenant->slug);

        return (new MailMessage)
            ->subject("[Feeto] Tu suscripción vence en {$daysLeft} día(s) — Renueva ahora")
            ->greeting("Hola, {$notifiable->name}")
            ->line("La suscripción de tu taller **{$this->tenant->name}** vence el **{$expiryDate}**.")
            ->line('Para mantener el acceso ininterrumpido a todas tus funciones, renueva tu plan antes de esa fecha.')
            ->action('Renovar suscripción', $checkoutUrl)
            ->line('Si ya realizaste el pago, puedes ignorar este correo.')
            ->salutation('El equipo de Feeto');
    }
}
