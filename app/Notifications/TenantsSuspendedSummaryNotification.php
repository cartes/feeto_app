<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class TenantsSuspendedSummaryNotification extends Notification
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
            ->subject("[Taller Flow] {$count} taller(es) suspendido(s) hoy")
            ->greeting('Resumen de suspensiones automáticas')
            ->line("Los siguientes **{$count}** taller(es) fueron suspendidos hoy por suscripción vencida:");

        foreach ($this->tenants as $tenant) {
            $expiryDate = $tenant->subscription_ends_at?->format('d/m/Y') ?? 'desconocida';
            $mail->line("• **{$tenant->name}** — venció el {$expiryDate}");
        }

        return $mail->line('Se notificó al administrador de cada taller por correo electrónico.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'suspended_count' => $this->tenants->count(),
        ];
    }
}
