<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTenantWelcomeRequest;
use App\Models\Tenant;
use App\Services\TenantWelcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantWelcomeController extends Controller
{
    public function show(Request $request, TenantWelcomeService $welcome): Response|RedirectResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant && ($request->user()->is_super_admin || $request->user()->tenant_id === $tenant->id), 403);
        $state = $welcome->forTenant($tenant);

        if ($state['completed']) {
            return redirect($state['dashboard_url']);
        }

        return Inertia::render('TenantWelcome', ['welcome' => $state]);
    }

    public function update(UpdateTenantWelcomeRequest $request, TenantWelcomeService $welcome): RedirectResponse
    {
        $tenant = Tenant::current();
        $data = $request->validated();
        $welcome->update($tenant, $data);

        if ($data['action'] === 'save') {
            return redirect()->route('taller.welcome.show', ['tenantBySlug' => $tenant->slug, 'step' => (int) $data['step'] + 1]);
        }

        return redirect()->route('taller.dashboard', ['tenantBySlug' => $tenant->slug])
            ->with('success', $data['action'] === 'finish' ? 'Tu página está preparada. Ya puedes compartirla con tus clientes.' : 'Puedes retomar la bienvenida desde Inicio.');
    }
}
