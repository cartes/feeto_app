<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\TrialRequestSubmitted;
use App\Http\Requests\StoreTrialRequest;
use App\Models\TrialRequest;
use Illuminate\Http\RedirectResponse;
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

    public function store(StoreTrialRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $submittedEmail = $validated['email'];

        unset($validated['terms']);

        $trialRequest = TrialRequest::create($validated);

        TrialRequestSubmitted::dispatch($trialRequest);

        return redirect()
            ->route('trial.success')
            ->with('trial_request_email', $submittedEmail);
    }

    public function success(): Response
    {
        return Inertia::render('Trial/Success', [
            'email' => session('trial_request_email'),
            'redirect_url' => route('home'),
            'redirect_delay_seconds' => 6,
        ]);
    }
}
