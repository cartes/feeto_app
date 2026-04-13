<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Tenant;
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
