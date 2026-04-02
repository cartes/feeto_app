<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(): Response
    {
        $appointments = Appointment::with(['client', 'vehicle'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->get()
            ->map(fn (Appointment $a): array => [
                'id'               => $a->id,
                'plate'            => $a->plate,
                'appointment_date' => $a->appointment_date->format('H:i'),
                'status'           => $a->status,
                'notes'            => $a->notes,
                'client'           => $a->client ? [
                    'id'    => $a->client->id,
                    'name'  => $a->client->name,
                    'rut'   => $a->client->rut,
                    'phone' => $a->client->phone,
                ] : null,
                'vehicle' => $a->vehicle ? [
                    'id'    => $a->vehicle->id,
                    'plate' => $a->vehicle->plate,
                    'brand' => $a->vehicle->brand,
                    'model' => $a->vehicle->model,
                    'color' => $a->vehicle->color,
                ] : null,
            ]);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'today'        => now()->toDateString(),
        ]);
    }
}
