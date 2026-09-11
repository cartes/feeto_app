<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyActivityReportNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación de reporte diario.
     *
     * @param  array{
     *     report_date: string,
     *     total_active_tenants: int,
     *     new_tenants_today: int,
     *     new_trial_requests_today: int,
     *     pending_trial_requests: int,
     *     work_orders_today: int,
     *     quotes_today: int,
     *     logins_today: int,
     *     visits_today: int,
     *     expiring_tenants_count: int,
     *     expiring_tenants_list?: array<int, string>,
     *     is_test?: bool,
     * }  $metrics
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
        $tag = ! empty($this->metrics['is_test']) ? '[PRUEBA] ' : '';
        $subject = "{$tag}[Taller Flow] Informe diario de actividad — {$this->metrics['report_date']}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Resumen Diario de Actividad')
            ->line("Este es el balance general de operaciones registrado el día **{$this->metrics['report_date']}**.");

        if (! empty($this->metrics['is_test'])) {
            $mail->line('ℹ️ **Este correo corresponde a un envío manual de prueba desde el Dashboard de Super-Admin.**');
        }

        $mail->line('---')
            ->line('### 🏢 Talleres y Clientes')
            ->line("**Talleres activos totales:** {$this->metrics['total_active_tenants']}")
            ->line("**Nuevos talleres registrados hoy:** {$this->metrics['new_tenants_today']}")
            ->line('---')
            ->line('### 🔧 Operación del Día')
            ->line("**Órdenes de Trabajo (OTs) creadas hoy:** {$this->metrics['work_orders_today']}")
            ->line("**Cotizaciones emitidas hoy:** {$this->metrics['quotes_today']}")
            ->line("**Inicios de sesión (logins):** {$this->metrics['logins_today']}")
            ->line("**Visitas registradas (páginas vistas):** {$this->metrics['visits_today']}")
            ->line('---')
            ->line('### 🧪 Solicitudes de Prueba (Trials)')
            ->line("**Nuevas solicitudes hoy:** {$this->metrics['new_trial_requests_today']}")
            ->line("**Solicitudes pendientes de aprobación:** {$this->metrics['pending_trial_requests']}");

        if ($this->metrics['pending_trial_requests'] > 0) {
            $mail->action('Revisar solicitudes en el panel', route('admin.trial-requests.index'));
        }

        $mail->line('---')
            ->line('### ⚠️ Suscripciones y Alertas');

        if ($this->metrics['expiring_tenants_count'] > 0) {
            $mail->line("**Suscripciones por vencer en 7 días:** {$this->metrics['expiring_tenants_count']}");

            if (! empty($this->metrics['expiring_tenants_list'])) {
                foreach ($this->metrics['expiring_tenants_list'] as $tenantInfo) {
                    $mail->line("• {$tenantInfo}");
                }
            }
        } else {
            $mail->line('No hay suscripciones próximas a vencer. ✅');
        }

        return $mail->line('---')
            ->line('Informe generado automáticamente por Taller Flow.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->metrics;
    }
}
