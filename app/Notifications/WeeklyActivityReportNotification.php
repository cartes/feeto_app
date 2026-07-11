<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyActivityReportNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  array<string, int|string>  $metrics
     */
    public function __construct(public readonly array $metrics) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $period = "del {$this->metrics['week_start']} al {$this->metrics['week_end']}";

        $mail = (new MailMessage)
            ->subject('[Taller Flow] Informe semanal de actividad — '.$this->metrics['week_end'])
            ->greeting('Resumen semanal de la plataforma')
            ->line("Este es el informe de actividad correspondiente al período **{$period}**.")
            ->line('---')
            ->line('### 📊 Talleres')
            ->line("**Talleres activos totales:** {$this->metrics['total_active_tenants']}")
            ->line("**Nuevos talleres esta semana:** {$this->metrics['new_tenants']}")
            ->line('---')
            ->line('### 🧪 Solicitudes de Prueba')
            ->line("**Nuevas solicitudes esta semana:** {$this->metrics['new_trial_requests']}")
            ->line("**Solicitudes pendientes de revisión:** {$this->metrics['pending_trial_requests']}")
            ->when(
                $this->metrics['pending_trial_requests'] > 0,
                fn (MailMessage $m) => $m->action('Revisar solicitudes pendientes', route('admin.trial-requests.index'))
            )
            ->line('---')
            ->line('### 🔧 Órdenes de Trabajo')
            ->line("**Nuevas OTs esta semana:** {$this->metrics['new_work_orders']}")
            ->line('---')
            ->line('### ⚠️ Alertas');

        if ($this->metrics['expiring_tenants'] > 0) {
            $mail->line("**Suscripciones próximas a vencer (14 días):** {$this->metrics['expiring_tenants']}");
        } else {
            $mail->line('No hay suscripciones próximas a vencer. ✅');
        }

        return $mail->line('---')
            ->line('Este informe se envía automáticamente cada sábado al mediodía.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->metrics;
    }
}
