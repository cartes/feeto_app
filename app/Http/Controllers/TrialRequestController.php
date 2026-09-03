<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Country;
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
        $countries = array_map(static fn (Country $c): array => [
            'value' => $c->value,
            'label' => $c->label(),
            'flag' => $c->flag(),
            'phone_placeholder' => $c->phonePlaceholder(),
        ], Country::operational());

        return Inertia::render('Trial/Create', [
            'countries' => $countries,
            'seo' => $this->resolveMarketingSeo('trial'),
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
