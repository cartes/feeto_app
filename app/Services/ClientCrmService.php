<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\InternalNote;
use App\Models\Quote;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClientCrmService
{
    /**
     * @return array{
     *     client: array<string, mixed>,
     *     vehicles: array<int, array<string, mixed>>,
     *     historyTimeline: array<int, array<string, mixed>>,
     *     internalNotes: array<int, array<string, mixed>>,
     *     crmMetrics: array<string, mixed>,
     *     quoteSummary: array<string, mixed>,
     *     invoiceSummary: array<string, mixed>,
     *     invoices: array<int, array<string, mixed>>
     * }
     */
    public function buildProfile(Client $client): array
    {
        $vehicles = $client->vehicles()
            ->withCount('workOrders')
            ->withMax('workOrders as last_work_order_at', 'created_at')
            ->orderBy('plate')
            ->get();

        $workOrders = WorkOrder::query()
            ->whereHas('vehicle', function ($query) use ($client): void {
                $query->where('client_id', $client->id);
            })
            ->with([
                'vehicle:id,client_id,plate,brand,model,color,vin',
                'quote:id,work_order_id,status,subtotal_amount,sent_at,responded_at',
            ])
            ->latest()
            ->get();

        $appointments = $client->appointments()
            ->with('vehicle:id,client_id,plate,brand,model,color,vin')
            ->orderByDesc('appointment_date')
            ->get();

        $internalNotes = $client->internalNotes()
            ->with('user:id,name')
            ->latest()
            ->get();

        $invoices = $client->invoices()
            ->with([
                'workOrder.vehicle:id,client_id,plate,brand,model,color,vin',
                'quote:id,work_order_id,status,subtotal_amount',
            ])
            ->orderByDesc('due_at')
            ->get();

        $finishedWorkOrders = $workOrders->where('status', WorkOrder::STATUS_LISTO);
        $quotes = $workOrders
            ->map(fn (WorkOrder $workOrder): ?Quote => $workOrder->quote)
            ->filter();

        $lastVisitAt = $this->latestDate(
            $workOrders->first()?->created_at,
            $appointments->first()?->appointment_date,
        );

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'rut' => $client->rut,
                'phone' => $client->phone,
                'email' => $client->email,
                'max_credit_limit' => (float) ($client->max_credit_limit ?? 0),
                'created_at' => $client->created_at?->toISOString(),
            ],
            'vehicles' => $vehicles
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'plate' => $vehicle->plate,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                    'vin' => $vehicle->vin,
                    'work_orders_count' => (int) $vehicle->work_orders_count,
                    'last_work_order_at' => $this->normalizeDate($vehicle->last_work_order_at),
                ])
                ->values()
                ->all(),
            'historyTimeline' => $this->buildTimeline($workOrders, $appointments, $internalNotes),
            'internalNotes' => $internalNotes
                ->map(fn (InternalNote $note): array => [
                    'id' => $note->id,
                    'content' => $note->content,
                    'created_at' => $note->created_at?->toISOString(),
                    'user' => [
                        'name' => $note->user?->name,
                    ],
                ])
                ->values()
                ->all(),
            'crmMetrics' => [
                'total_spent' => (float) $finishedWorkOrders->sum('total_amount'),
                'last_visit' => $lastVisitAt,
                'average_ticket' => $finishedWorkOrders->isEmpty()
                    ? 0.0
                    : (float) ($finishedWorkOrders->sum('total_amount') / $finishedWorkOrders->count()),
                'vehicles_count' => $vehicles->count(),
                'visits_count' => $workOrders->count() + $appointments->count(),
                'notes_count' => $internalNotes->count(),
            ],
            'quoteSummary' => [
                'total_quotes' => $quotes->count(),
                'accepted_quotes' => $quotes->where('status', Quote::STATUS_ACCEPTED)->count(),
                'rejected_quotes' => $quotes->where('status', Quote::STATUS_REJECTED)->count(),
                'pending_quotes' => $quotes->where('status', Quote::STATUS_PENDING_CUSTOMER)->count(),
                'quoted_amount' => (float) $quotes->sum('subtotal_amount'),
                'accepted_amount' => (float) $quotes
                    ->where('status', Quote::STATUS_ACCEPTED)
                    ->sum('subtotal_amount'),
            ],
            'invoiceSummary' => [
                'total_invoices' => $invoices->count(),
                'overdue_invoices' => $invoices->filter(fn (ClientInvoice $invoice): bool => $invoice->isOverdue())->count(),
                'total_amount' => (float) $invoices->sum('amount_total'),
                'amount_due' => (float) $invoices->sum('amount_due'),
                'overdue_amount' => (float) $invoices
                    ->filter(fn (ClientInvoice $invoice): bool => $invoice->isOverdue())
                    ->sum('amount_due'),
            ],
            'invoices' => $invoices
                ->map(fn (ClientInvoice $invoice): array => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'amount_total' => (float) $invoice->amount_total,
                    'amount_due' => (float) $invoice->amount_due,
                    'issued_at' => $invoice->issued_at?->toDateString(),
                    'due_at' => $invoice->due_at?->toDateString(),
                    'last_whatsapp_reminder_sent_at' => $invoice->last_whatsapp_reminder_sent_at?->toISOString(),
                    'work_order_id' => $invoice->work_order_id,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildIndexItem(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'rut' => $client->rut,
            'phone' => $client->phone,
            'email' => $client->email,
            'metrics' => [
                'vehicles_count' => (int) ($client->vehicles_count ?? 0),
                'visits_count' => (int) ($client->work_orders_count ?? 0) + (int) ($client->appointments_count ?? 0),
                'notes_count' => (int) ($client->internal_notes_count ?? 0),
                'total_spent' => (float) ($client->total_spent ?? 0),
                'last_visit' => $this->latestDate(
                    $this->parseDate($client->latest_work_order_at ?? null),
                    $this->parseDate($client->latest_appointment_at ?? null),
                ),
            ],
        ];
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, InternalNote>  $internalNotes
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(
        Collection $workOrders,
        Collection $appointments,
        Collection $internalNotes,
    ): array {
        return $workOrders
            ->map(fn (WorkOrder $workOrder): array => [
                'id' => 'work-order-'.$workOrder->id,
                'type' => 'work_order',
                'type_label' => 'Orden de trabajo',
                'title' => 'OT #'.$workOrder->id,
                'subtitle' => $this->vehicleLabel($workOrder->vehicle),
                'description' => $workOrder->observations ?: 'Sin observaciones registradas.',
                'occurred_at' => $workOrder->created_at?->toISOString(),
                'status' => $workOrder->status,
                'status_label' => $this->workOrderStatusLabel($workOrder->status),
                'tracking_url' => $workOrder->uuid ? route('tracking.show', $workOrder->uuid) : null,
                'amount' => (float) ($workOrder->total_amount ?? 0),
                'quote' => $workOrder->quote ? [
                    'status' => $workOrder->quote->status,
                    'amount' => (float) $workOrder->quote->subtotal_amount,
                ] : null,
            ])
            ->concat($appointments->map(fn (Appointment $appointment): array => [
                'id' => 'appointment-'.$appointment->id,
                'type' => 'appointment',
                'type_label' => 'Cita',
                'title' => 'Cita '.$this->appointmentStatusLabel($appointment->status),
                'subtitle' => $this->vehicleLabel($appointment->vehicle, $appointment->plate),
                'description' => $appointment->pre_check_notes ?: ($appointment->notes ?: 'Sin notas registradas.'),
                'occurred_at' => $appointment->appointment_date?->toISOString(),
                'status' => $appointment->status,
                'status_label' => $this->appointmentStatusLabel($appointment->status),
                'tracking_url' => null,
                'amount' => null,
                'quote' => null,
            ]))
            ->concat($internalNotes->map(fn (InternalNote $note): array => [
                'id' => 'internal-note-'.$note->id,
                'type' => 'internal_note',
                'type_label' => 'Nota interna',
                'title' => 'Nota interna',
                'subtitle' => $note->user?->name ?? 'Equipo interno',
                'description' => $note->content,
                'occurred_at' => $note->created_at?->toISOString(),
                'status' => null,
                'status_label' => null,
                'tracking_url' => null,
                'amount' => null,
                'quote' => null,
            ]))
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }

    private function vehicleLabel(?Vehicle $vehicle, ?string $fallbackPlate = null): string
    {
        if ($vehicle instanceof Vehicle) {
            $plate = $vehicle->plate ?: $fallbackPlate;
            $label = trim(implode(' ', array_filter([$vehicle->brand, $vehicle->model])));

            if ($plate !== null && $label !== '') {
                return $label.' · '.$plate;
            }

            return $label !== '' ? $label : $plate;
        }

        return $fallbackPlate ?? 'Vehículo no identificado';
    }

    private function workOrderStatusLabel(?string $status): string
    {
        return match ($status) {
            WorkOrder::STATUS_RECEPCION => 'En recepción',
            WorkOrder::STATUS_DIAGNOSTICO => 'En diagnóstico',
            WorkOrder::STATUS_ESPERANDO_REPUESTOS => 'Esperando repuestos',
            WorkOrder::STATUS_CONTROL_CALIDAD => 'Control de calidad',
            WorkOrder::STATUS_LISTO => 'Listo / entregado',
            'taller' => 'En taller',
            'aviso_cliente' => 'Aviso a cliente',
            default => $status ?? 'Sin estado',
        };
    }

    private function appointmentStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'pendiente',
            'arrived' => 'recepcionada',
            'cancelled' => 'cancelada',
            default => $status ?? 'sin estado',
        };
    }

    private function latestDate(?CarbonInterface $first, ?CarbonInterface $second): ?string
    {
        $dates = array_filter([$first, $second], static fn (?CarbonInterface $date): bool => $date !== null);

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (CarbonInterface $left, CarbonInterface $right): int => $right->getTimestamp() <=> $left->getTimestamp());

        return $dates[0]->toISOString();
    }

    private function normalizeDate(mixed $value): ?string
    {
        return $this->parseDate($value)?->toISOString();
    }

    private function parseDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
