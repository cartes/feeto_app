<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpsertServiceRequest;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(Request $request): Response
    {
        $this->ensureFeatureEnabled();

        $search = $request->input('search');

        $services = Service::query()
            ->when($search, function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(UpsertServiceRequest $request): RedirectResponse
    {
        $this->ensureFeatureEnabled();

        Service::create($request->validated());

        return redirect()->route('services.index')->with('success', 'Servicio agregado exitosamente.');
    }

    public function update(UpsertServiceRequest $request, Service $service): RedirectResponse
    {
        $this->ensureFeatureEnabled();

        $service->update($request->validated());

        return redirect()->route('services.index')->with('success', 'Servicio actualizado.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->ensureFeatureEnabled();

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }

    private function ensureFeatureEnabled(): void
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }
}
