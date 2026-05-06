<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\ClientCrmService;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(protected ClientCrmService $clientCrmService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $clients = Client::query()
            ->when($search, function ($query, $search): void {
                $escaped = addcslashes($search, '%_');
                $query->where('name', 'like', "%{$escaped}%")
                    ->orWhere('rut', 'like', "%{$escaped}%");
            })
            ->select('clients.*')
            ->withCount(['vehicles', 'appointments', 'internalNotes'])
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->selectRaw('count(*)'),
                'work_orders_count',
            )
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->where('work_orders.status', WorkOrder::STATUS_LISTO)
                    ->selectRaw('coalesce(sum(work_orders.total_amount), 0)'),
                'total_spent',
            )
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->selectRaw('max(work_orders.created_at)'),
                'latest_work_order_at',
            )
            ->withMax('appointments as latest_appointment_at', 'appointment_date')
            ->latest()
            ->paginate(15)
            ->through(fn (Client $client): array => $this->clientCrmService->buildIndexItem($client))
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return redirect()->back()->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): Response
    {
        $profile = $this->clientCrmService->buildProfile($client);
        $tenant = Tenant::current();

        return Inertia::render('Clients/Show', [
            ...$profile,
            'salesManagementEnabled' => $tenant?->hasFeature(PlanFeatureService::FEATURE_SALES_MANAGEMENT) ?? false,
        ]);
    }
}
