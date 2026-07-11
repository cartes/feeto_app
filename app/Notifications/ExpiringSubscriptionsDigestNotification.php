<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ExpiringSubscriptionsDigestNotification extends Notification
{
    use Queueable;

    /** @param Collection<int, Tenant> $tenants */
    public function __construct(public readonly Collection $tenants) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->tenants->count();
        $mail = (new MailMessage)
            ->subject("[Taller Flow] {$count} suscripción(es) próximas a vencer")
            ->greeting('Alerta de suscripciones')
            ->line("Los siguientes **{$count}** taller(es) tienen su suscripción próxima a vencer en los próximos 7 días:");

        foreach ($this->tenants as $tenant) {
            $daysLeft = (int) now()->diffInDays($tenant->subscription_ends_at, false);
            $daysLabel = $daysLeft <= 1 ? 'hoy' : "en {$daysLeft} días";
            $mail->line("• **{$tenant->name}** — vence {$daysLabel} ({$tenant->subscription_ends_at->format('d/m/Y')})");
        }

        return $mail->line('Recuerda hacer seguimiento para evitar suspensiones automáticas.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'expiring_count' => $this->tenants->count(),
        ];
    }
}
