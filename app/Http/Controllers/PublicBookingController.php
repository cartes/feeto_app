<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function show(Tenant $tenantBySlug): Response
    {
        return Inertia::render('Public/TenantLanding', [
            'tenant' => [
                'id' => $tenantBySlug->id,
                'name' => $tenantBySlug->name,
                'slug' => $tenantBySlug->slug,
                'domain' => $tenantBySlug->domain,
                'rut_taller' => $tenantBySlug->rut_taller,
                'plan' => $tenantBySlug->plan,
            ],
        ]);
    }

    public function store(Request $request, Tenant $tenantBySlug): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'plate' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'pre_check_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Verificar disponibilidad: no permitir citas dentro de ±30 min del mismo taller
        $date = new Carbon($validated['appointment_date']);
        $conflict = Appointment::where('tenant_id', $tenantBySlug->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_date', [
                $date->copy()->subMinutes(30),
                $date->copy()->addMinutes(30),
            ])
            ->exists();

        if ($conflict) {
            return back()
                ->withErrors(['appointment_date' => 'Ese horario ya está reservado. Por favor elige otro con al menos 30 minutos de diferencia.'])
                ->withInput();
        }

        Appointment::create([
            'tenant_id' => $tenantBySlug->id,
            'plate' => strtoupper($validated['plate']),
            'customer_name' => $validated['customer_name'],
            'phone' => $validated['phone'],
            'appointment_date' => $validated['appointment_date'],
            'pre_check_notes' => $validated['pre_check_notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('booking_success', true);
    }
}
