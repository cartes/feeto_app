<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrialRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Trial/Create', [
            'seo' => $this->resolveMarketingSeo(
                'trial',
                'Prueba Gratis 14 días · TallerFlow — Software para Talleres',
                'Solicita tu acceso gratuito de 14 días a TallerFlow. Sin tarjeta de crédito. Sin compromisos. Activa tu taller digital hoy.',
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:trial_requests,email',
            'phone' => 'required|string|max:30',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'users_estimate' => 'nullable|integer|min:1|max:999',
            'requested_plan' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:1000',
            'terms' => 'accepted',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe una solicitud con este correo electrónico.',
            'phone.required' => 'El teléfono es obligatorio.',
            'business_name.required' => 'El nombre del negocio es obligatorio.',
            'business_type.required' => 'El rubro del negocio es obligatorio.',
            'terms.accepted' => 'Debes aceptar los términos para continuar.',
        ]);

        unset($validated['terms']);

        TrialRequest::create($validated);

        return redirect()->route('trial.success');
    }

    public function success(): Response
    {
        return Inertia::render('Trial/Success');
    }
}
