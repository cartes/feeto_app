<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClientInvoice;
use App\Models\QuoteEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TallerDashboardController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403, 'No tienes acceso a este taller.');
        }

        $initialActivities = WorkOrder::query()
            ->with('vehicle')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (WorkOrder $order): array => [
                'work_order_id' => $order->id,
                'plate' => $order->vehicle->plate ?? 'N/A',
                'vehicle' => trim(($order->vehicle->brand ?? '').' '.($order->vehicle->model ?? '')),
                'new_status' => $order->status,
                'timestamp' => $order->updated_at->toISOString(),
            ]);

        $quoteNotifications = $tenant->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)
            ? QuoteEvent::query()
                ->with('workOrder.vehicle.client')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (QuoteEvent $event): array => [
                    'id' => $event->id,
                    'description' => $event->description,
                    'event_type' => $event->event_type,
                    'actor_type' => $event->actor_type,
                    'timestamp' => $event->created_at->toISOString(),
                    'work_order_id' => $event->work_order_id,
                    'plate' => $event->workOrder?->vehicle?->plate ?? 'N/A',
                    'client' => $event->workOrder?->vehicle?->client?->name ?? 'Cliente',
                ])
            : collect();

        $calendarStart = now()->startOfMonth()->startOfDay();
        $calendarEnd = now()->endOfMonth()->endOfDay();

        $appointments = Appointment::query()
            ->with(['client', 'vehicle'])
            ->whereBetween('appointment_date', [$calendarStart, $calendarEnd])
            ->orderBy('appointment_date')
            ->get();

        $serializedAppointments = $appointments
            ->map(fn (Appointment $appointment): array => $this->serializeAppointment($appointment))
            ->values();

        $todayAppointments = $appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->appointment_date->isToday())
            ->map(fn (Appointment $appointment): array => $this->serializeAppointment($appointment))
            ->values();

        $appointmentNotifications = $appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->appointment_date->isToday() || $appointment->appointment_date->isFuture())
            ->take(5)
            ->map(fn (Appointment $appointment): array => $this->serializeNotification($appointment))
            ->values();

        $overdueInvoices = $user->hasPermissionTo('financials.view') && $tenant->hasFeature(PlanFeatureService::FEATURE_SALES_MANAGEMENT)
            ? ClientInvoice::query()
                ->with('client')
                ->whereIn('status', [
                    ClientInvoice::STATUS_PENDING,
                    ClientInvoice::STATUS_PARTIAL,
                    ClientInvoice::STATUS_OVERDUE,
                ])
                ->whereDate('due_at', '<', now()->toDateString())
                ->where('amount_due', '>', 0)
                ->orderBy('due_at')
                ->limit(5)
                ->get()
                ->map(fn (ClientInvoice $invoice): array => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client?->name ?? 'Cliente',
                    'amount_due' => (float) $invoice->amount_due,
                    'due_at' => $invoice->due_at?->toDateString(),
                    'days_overdue' => (int) $invoice->due_at->diffInDays(now()),
                ])
            : collect();

        return Inertia::render('Dashboard', [
            'initialActivities' => $initialActivities,
            'quoteNotifications' => $quoteNotifications,
            'appointments' => $serializedAppointments,
            'todayAppointments' => $todayAppointments,
            'appointmentNotifications' => $appointmentNotifications,
            'overdueInvoices' => $overdueInvoices,
            'today' => now()->toDateString(),
            'tenant' => $tenant->only('id', 'name', 'slug'),
            'calendarRange' => [
                'start' => $calendarStart->toDateString(),
                'end' => $calendarEnd->toDateString(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAppointment(Appointment $appointment): array
    {
        $clientName = $appointment->client?->name ?? $appointment->customer_name ?? 'Cliente sin registrar';
        $plate = $appointment->plate ?: $appointment->vehicle?->plate ?? 'N/A';

        return [
            'id' => $appointment->id,
            'plate' => $plate,
            'date' => $appointment->appointment_date->toDateString(),
            'time' => $appointment->appointment_date->format('H:i'),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'scheduled_at' => $appointment->appointment_date->toISOString(),
            'client' => [
                'id' => $appointment->client?->id,
                'name' => $clientName,
                'rut' => $appointment->client?->rut,
                'phone' => $appointment->client?->phone ?? $appointment->phone,
            ],
            'vehicle' => $appointment->vehicle ? [
                'id' => $appointment->vehicle->id,
                'plate' => $appointment->vehicle->plate,
                'brand' => $appointment->vehicle->brand,
                'model' => $appointment->vehicle->model,
                'color' => $appointment->vehicle->color,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNotification(Appointment $appointment): array
    {
        $clientName = $appointment->client?->name ?? $appointment->customer_name ?? 'Cliente sin registrar';
        $plate = $appointment->plate ?: $appointment->vehicle?->plate ?? 'N/A';

        return [
            'id' => $appointment->id,
            'title' => "Cita de {$clientName}",
            'description' => "Patente {$plate} · {$this->formatDateLabel($appointment->appointment_date)} a las {$appointment->appointment_date->format('H:i')} hrs",
            'status' => $appointment->status,
            'date' => $appointment->appointment_date->toDateString(),
            'time' => $appointment->appointment_date->format('H:i'),
        ];
    }

    private function formatDateLabel(CarbonInterface $date): string
    {
        if ($date->isToday()) {
            return 'hoy';
        }

        if ($date->isTomorrow()) {
            return 'mañana';
        }

        return $date->locale('es_CL')->translatedFormat('d \d\e M');
    }
}
