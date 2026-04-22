<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\WorkOrderStatusUpdated;
use App\Http\Requests\AddQuoteItemRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    /**
     * Muestra el Tablero Kanban con todas las órdenes de trabajo del taller.
     */
    public function index(): Response
    {
        $orders = WorkOrder::with(['vehicle.client', 'quote'])->get()->groupBy('status');

        $kanban = collect(WorkOrder::statuses())
            ->mapWithKeys(fn (string $status): array => [$status => $orders->get($status, [])])
            ->all();

        return Inertia::render('WorkOrders/Index', [
            'kanban' => $kanban,
            'tenantId' => Tenant::current() ? Tenant::current()->id : 0,
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

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'products' => $products,
            'services' => $services,
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
        $quote = $this->quoteFor($workOrder);

        $itemPayload = $this->buildItemPayload($validated);
        $quote->items()->create($itemPayload);

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordQuoteEvent($quote, 'staff', 'item_added', 'Se agregó un ítem a la cotización.', [
            'description' => $itemPayload['description'],
            'item_type' => $itemPayload['item_type'],
        ]);

        return back();
    }

    /**
     * Elimina un ítem de la Orden de Trabajo y devuelve el stock si aplica.
     */
    public function removeItem(WorkOrder $workOrder, QuoteItem $item): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($workOrder);
        $this->ensureCommercialQuotesEnabled();

        $quote = $this->quoteFor($workOrder);

        if ($item->quote_id !== $quote->id) {
            throw new ModelNotFoundException;
        }

        $description = $item->description;
        $item->delete();

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordQuoteEvent($quote, 'staff', 'item_removed', 'Se eliminó un ítem de la cotización.', [
            'description' => $description,
        ]);

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
        ]);

        $oldStatus = $workOrder->status;
        $workOrder->update(['status' => $validated['status']]);

        Log::info('Dispatching WorkOrderStatusUpdated', [
            'work_order_id' => $workOrder->id,
            'tenant_id' => $workOrder->tenant_id,
            'new_status' => $validated['status'],
        ]);

        broadcast(new WorkOrderStatusUpdated(
            $workOrder->load('vehicle'),
            $oldStatus,
            $validated['status']
        ));

        return back();
    }

    /**
     * Recalcula y persiste el total de la Orden de Trabajo.
     */
    private function recalculateTotal(WorkOrder $workOrder, Quote $quote): void
    {
        $total = (float) $quote->items()->sum('total_price');
        $quote->update(['subtotal_amount' => $total]);
        $workOrder->update(['total_amount' => $total]);
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

    private function quoteFor(WorkOrder $workOrder): Quote
    {
        return $workOrder->quote()->firstOrCreate([
            'work_order_id' => $workOrder->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildItemPayload(array $validated): array
    {
        $product = null;
        $service = null;
        $itemType = QuoteItem::TYPE_MANUAL;
        $description = (string) ($validated['description'] ?? '');
        $unitPrice = (float) ($validated['unit_price'] ?? 0);

        if (! empty($validated['product_id'])) {
            $product = Product::query()->findOrFail($validated['product_id']);
            $itemType = QuoteItem::TYPE_PRODUCT;
            $description = $product->name;
            $unitPrice = (float) $product->selling_price;
        }

        if (! empty($validated['service_id'])) {
            $service = Service::query()->findOrFail($validated['service_id']);
            $itemType = QuoteItem::TYPE_SERVICE;
            $description = $service->name;
            $unitPrice = (float) $service->selling_price;
        }

        $quantity = (float) $validated['quantity'];

        return [
            'product_id' => $product?->id,
            'service_id' => $service?->id,
            'item_type' => $itemType,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ];
    }

    private function markQuoteAsDraft(Quote $quote): void
    {
        $quote->update([
            'status' => Quote::STATUS_DRAFT,
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordQuoteEvent(
        Quote $quote,
        string $actorType,
        string $eventType,
        string $description,
        array $metadata = []
    ): void {
        QuoteEvent::create([
            'tenant_id' => $quote->tenant_id,
            'work_order_id' => $quote->work_order_id,
            'quote_id' => $quote->id,
            'actor_type' => $actorType,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
