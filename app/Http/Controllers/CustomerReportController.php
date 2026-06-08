<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\ClientCrmService;
use App\Services\PlanFeatureService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerReportController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected ClientCrmService $clientCrmService,
    ) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        $ninetyDaysAgo = now()->subDays(90);

        $clients = $this->clientMetricsQuery()
            ->get()
            ->map(fn (Client $client): array => $this->clientCrmService->buildIndexItem($client));

        $activeClients = $clients->filter(
            fn (array $client): bool => $this->isRecentVisit($client['metrics']['last_visit'] ?? null, $ninetyDaysAgo)
        );

        $inactiveClients = $clients->filter(
            fn (array $client): bool => ! $this->isRecentVisit($client['metrics']['last_visit'] ?? null, $ninetyDaysAgo)
        );

        $completedWorkOrders = WorkOrder::query()
            ->where('status', WorkOrder::STATUS_LISTO);

        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        $overdueClients = $overdueInvoices
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                /** @var ClientInvoice $invoice */
                $invoice = $group->first();

                return [
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client?->name ?? 'Cliente',
                    'invoice_count' => $group->count(),
                    'amount_due' => (float) $group->sum('amount_due'),
                ];
            })
            ->sortByDesc('amount_due')
            ->take(8)
            ->values();

        return Inertia::render('Reports/Customers', [
            'summary' => [
                'total_clients' => $clients->count(),
                'active_clients' => $activeClients->count(),
                'inactive_clients' => $inactiveClients->count(),
                'clients_with_overdue_invoices' => $overdueInvoices->pluck('client_id')->filter()->unique()->count(),
                'lifetime_value' => (float) $completedWorkOrders->sum('total_amount'),
                'average_ticket' => (float) ($completedWorkOrders->avg('total_amount') ?? 0),
            ],
            'topClients' => $clients
                ->sortByDesc(fn (array $client): float => (float) ($client['metrics']['total_spent'] ?? 0))
                ->take(8)
                ->values(),
            'inactiveClients' => $inactiveClients
                ->sortBy(fn (array $client): string => $client['metrics']['last_visit'] ?? '')
                ->take(8)
                ->values(),
            'overdueClients' => $overdueClients,
        ]);
    }

    private function clientMetricsQuery(): Builder
    {
        return Client::query()
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
            ->withMax('appointments as latest_appointment_at', 'appointment_date');
    }

    private function isRecentVisit(?string $value, CarbonInterface $threshold): bool
    {
        if (! filled($value)) {
            return false;
        }

        return Carbon::parse($value)->gte($threshold);
    }
}
