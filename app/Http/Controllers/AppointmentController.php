<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(): Response
    {
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
            ->take(6)
            ->map(fn (Appointment $appointment): array => $this->serializeNotification($appointment))
            ->values();

        return Inertia::render('Appointments/Index', [
            'appointments' => $serializedAppointments,
            'todayAppointments' => $todayAppointments,
            'appointmentNotifications' => $appointmentNotifications,
            'today' => now()->toDateString(),
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
            'appointment_date' => $appointment->appointment_date->format('H:i'),
            'scheduled_at' => $appointment->appointment_date->toISOString(),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
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
