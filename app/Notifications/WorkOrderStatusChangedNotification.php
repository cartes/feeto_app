<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly string $oldStatus,
        public readonly string $newStatus,
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
        $clientName = $this->workOrder->vehicle?->client?->name ?: 'cliente';
        $plate = $this->workOrder->vehicle?->plate ?: 'sin patente';
        $statusLabel = $this->statusLabel($this->newStatus);
        $previousStatusLabel = $this->statusLabel($this->oldStatus);
        $trackingUrl = route('tracking.show', ['uuid' => $this->workOrder->uuid]);

        return (new MailMessage)
            ->subject("[Taller Flow] Tu vehiculo {$plate} ahora esta {$statusLabel}")
            ->greeting("Hola, {$clientName}")
            ->line("La orden de trabajo de tu vehiculo {$plate} cambio de estado de {$previousStatusLabel} a {$statusLabel}.")
            ->action('Ver seguimiento de tu vehiculo', $trackingUrl)
            ->line('Te avisaremos si hay nuevos cambios en el proceso.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            WorkOrder::STATUS_RECEPCION => 'en recepcion',
            WorkOrder::STATUS_DIAGNOSTICO => 'en diagnostico',
            WorkOrder::STATUS_ESPERANDO_REPUESTOS => 'esperando repuestos',
            WorkOrder::STATUS_CONTROL_CALIDAD => 'en control de calidad',
            WorkOrder::STATUS_LISTO => 'listo para entrega',
            'taller' => 'en taller',
            'aviso_cliente' => 'en aviso a cliente',
            default => $status,
        };
    }
}
