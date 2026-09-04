<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\WorkOrderStatusUpdated;
use App\Http\Requests\AddQuoteItemRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use App\Services\UfService;
use App\Services\VehicleCatalogService;
use App\Services\WorkOrderQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected UfService $ufService,
        protected WorkOrderQuoteService $workOrderQuoteService,
    ) {}

    /**
     * Muestra el Tablero Kanban o el Listado con todas las órdenes de trabajo del taller.
     */
    public function index(Request $request, VehicleCatalogService $vehicleCatalogService): Response
    {
        $statuses = WorkOrder::statuses();
        $tenantId = Tenant::current()?->id;

        $viewMode = $request->input('view', 'kanban');
        if (! in_array($viewMode, ['kanban', 'list'], true)) {
            $viewMode = 'kanban';
        }

        $month = $request->input('month');
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 20, 50, 100], true)) {
            $perPage = 15;
        }

        $query = WorkOrder::query()
            ->select(['id', 'uuid', 'vehicle_id', 'status', 'created_at', 'tenant_id', 'total_amount'])
            ->with([
                'vehicle' => fn ($query) => $query->select(['id', 'client_id', 'plate', 'brand', 'model', 'tenant_id']),
                'vehicle.client' => fn ($query) => $query->select(['id', 'name', 'tenant_id']),
                'quote' => fn ($query) => $query->select(['id', 'work_order_id', 'status', 'tenant_id']),
            ])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        // Month filter (YYYY-MM)
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $m] = explode('-', $month);
            $query->whereYear('created_at', (int) $year)
                ->whereMonth('created_at', (int) $m);
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }
                $q->orWhereHas('vehicle', function ($q2) use ($search) {
                    $q2->where('plate', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        if ($viewMode === 'list') {
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
            $kanban = [];
        } else {
            $orders = [];
            $kanbanOrders = $query->whereIn('status', $statuses)->get()->groupBy('status');
            $kanban = collect($statuses)
                ->mapWithKeys(fn (string $status): array => [$status => $kanbanOrders->get($status, [])])
                ->all();
        }

        return Inertia::render('WorkOrders/Index', [
            'kanban' => $kanban,
            'orders' => $orders,
            'tenantId' => $tenantId ?? 0,
            'vehicleCatalogBrands' => $vehicleCatalogService->brandOptions(),
            'filters' => [
                'view' => $viewMode,
                'month' => $month,
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Muestra el detalle completo de una Orden de Trabajo.
     */
    public function show(WorkOrder $workOrder): Response
    {
        $workOrder->load([
            'vehicle.client',
            'quote.items.product',
            'quote.items.service',
            'quote.events',
        ]);

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'selling_price', 'physical_stock'])
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->select(['id', 'name', 'code', 'selling_price', 'estimated_minutes'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tenant = Tenant::current();

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'taxName' => $workOrder->quote?->taxName() ?? $tenant?->taxName() ?? 'IVA',
            'defaultTaxRate' => $tenant?->defaultTaxRate() ?? 19.0,
            'products' => $products,
            'services' => $services,
            'uf_value' => $this->ufService->getCurrentValue(),
            'discountPolicy' => [
                'threshold' => $tenant?->maxDiscountWithoutApproval() ?? 10,
                'approver_roles' => ['Jefe', 'Supervisor'],
            ],
        ]);
    }

    /**
     * Agrega un ítem (repuesto o mano de obra) a la Orden de Trabajo.
     */
    public function addItem(AddQuoteItemRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($workOrder);
        $this->ensureCommercialQuotesEnabled();

        $validated = $request->validated();
        $itemPayload = $this->workOrderQuoteService->buildItemPayload($validated);
        $this->ensureDiscountIsAllowed($request, $itemPayload['discount_percent']);

        $this->workOrderQuoteService->addItem($workOrder, $validated);

        return back();
    }

    /**
     * Elimina un ítem de la Orden de Trabajo y devuelve el stock si aplica.
     */
    public function removeItem(WorkOrder $workOrder, QuoteItem $item): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($workOrder);
        $this->ensureCommercialQuotesEnabled();

        $this->workOrderQuoteService->removeItem($workOrder, $item);

        return back();
    }

    /**
     * Actualiza el estado de la Orden de Trabajo a través de llamadas asíncronas / Inertia (Drag and Drop).
     */
    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        // Verificar que el usuario pertenezca al tenant de la work order
        $this->authorizeWorkOrderAccess($workOrder);

        $validated = $request->validate([
            'status' => ['required', Rule::in(WorkOrder::statuses())],
            'confirmed_without_accepted_quote' => ['nullable', 'boolean'],
        ]);

        $oldStatus = $workOrder->status;

        if ($this->requiresUnacceptedQuoteConfirmation($workOrder, $validated['status']) && ! $request->boolean('confirmed_without_accepted_quote')) {
            return back()->with('warning', 'La cotización aún no está aceptada por el cliente. Debes confirmar el cambio para continuar.');
        }

        $workOrder->update(['status' => $validated['status']]);

        Log::info('Dispatching WorkOrderStatusUpdated', [
            'work_order_id' => $workOrder->id,
            'tenant_id' => $workOrder->tenant_id,
            'new_status' => $validated['status'],
        ]);

        event(new WorkOrderStatusUpdated(
            $workOrder->load('vehicle'),
            $oldStatus,
            $validated['status']
        ));

        return back();
    }

    /**
     * Elimina lógicamente (soft delete) la Orden de Trabajo.
     */
    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($workOrder);

        $workOrder->delete();

        return redirect()->route('work-orders.index')->with('success', 'Orden de trabajo eliminada exitosamente.');
    }

    /**
     * Verifica que el usuario autenticado tenga acceso a la work order.
     */
    private function authorizeWorkOrderAccess(WorkOrder $workOrder): void
    {
        $user = request()->user();

        if ($user->is_super_admin) {
            return;
        }

        if ($user->tenant_id !== $workOrder->tenant_id) {
            abort(403, 'No tienes permiso para modificar esta orden de trabajo.');
        }
    }

    private function ensureCommercialQuotesEnabled(): void
    {
        if (! Tenant::current()?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }

    private function ensureDiscountIsAllowed(Request $request, float $discountPercent): void
    {
        $tenant = Tenant::current();

        if (! $tenant || $discountPercent <= $tenant->maxDiscountWithoutApproval()) {
            return;
        }

        $user = $request->user();

        if ($user->is_super_admin || $user->hasAnyRole(['Jefe', 'Supervisor'])) {
            return;
        }

        throw ValidationException::withMessages([
            'discount_percent' => sprintf(
                'Los descuentos mayores a %s%% requieren rol Jefe o Supervisor.',
                rtrim(rtrim(number_format($tenant->maxDiscountWithoutApproval(), 2, '.', ''), '0'), '.')
            ),
        ]);
    }

    private function requiresUnacceptedQuoteConfirmation(WorkOrder $workOrder, string $newStatus): bool
    {
        if ($workOrder->status !== WorkOrder::STATUS_RECEPCION || $newStatus === WorkOrder::STATUS_RECEPCION) {
            return false;
        }

        $quoteStatus = $workOrder->quote()->value('status');

        return $quoteStatus !== Quote::STATUS_ACCEPTED;
    }
}
