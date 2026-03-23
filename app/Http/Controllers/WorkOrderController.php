<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\WorkOrderStatusUpdated;
use App\Http\Requests\StoreWorkOrderItemRequest;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderController extends Controller
{
    /**
     * Muestra el Tablero Kanban con todas las órdenes de trabajo del taller.
     */
    public function index(): Response
    {
        $orders = WorkOrder::with('vehicle.client')->get()->groupBy('status');

        $kanban = [
            'recepcion'           => $orders->get('recepcion', []),
            'diagnostico'         => $orders->get('diagnostico', []),
            'esperando_repuestos' => $orders->get('esperando_repuestos', []),
            'listo'               => $orders->get('listo', []),
        ];

        return Inertia::render('WorkOrders/Index', [
            'kanban'   => $kanban,
            'tenantId' => Tenant::current() ? Tenant::current()->id : 0,
        ]);
    }

    /**
     * Muestra el detalle completo de una Orden de Trabajo.
     */
    public function show(WorkOrder $workOrder): Response
    {
        $workOrder->load(['items.product', 'vehicle.client']);

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'selling_price', 'physical_stock'])
            ->orderBy('name')
            ->get();

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'products'  => $products,
        ]);
    }

    /**
     * Agrega un ítem (repuesto o mano de obra) a la Orden de Trabajo.
     */
    public function addItem(StoreWorkOrderItemRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validated();
        $totalPrice = $validated['quantity'] * $validated['unit_price'];

        $workOrder->items()->create([
            'product_id'  => $validated['product_id'] ?? null,
            'description' => $validated['description'],
            'quantity'    => $validated['quantity'],
            'unit_price'  => $validated['unit_price'],
            'total_price' => $totalPrice,
        ]);

        // Descontar stock si proviene de un producto
        if (! empty($validated['product_id'])) {
            Product::query()
                ->where('id', $validated['product_id'])
                ->decrement('physical_stock', $validated['quantity']);
        }

        $this->recalculateTotal($workOrder);

        return back();
    }

    /**
     * Elimina un ítem de la Orden de Trabajo y devuelve el stock si aplica.
     */
    public function removeItem(WorkOrder $workOrder, WorkOrderItem $item): RedirectResponse
    {
        // Restaurar stock si el ítem estaba ligado a un producto
        if ($item->product_id !== null) {
            Product::query()
                ->where('id', $item->product_id)
                ->increment('physical_stock', $item->quantity);
        }

        $item->delete();

        $this->recalculateTotal($workOrder);

        return back();
    }

    /**
     * Actualiza el estado de la Orden de Trabajo a través de llamadas asíncronas / Inertia (Drag and Drop).
     */
    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:recepcion,diagnostico,esperando_repuestos,listo',
        ]);

        $oldStatus = $workOrder->status;
        $workOrder->update(['status' => $validated['status']]);

        Log::info('Dispatching WorkOrderStatusUpdated', [
            'work_order_id' => $workOrder->id,
            'tenant_id'     => $workOrder->tenant_id,
            'new_status'    => $validated['status'],
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
    private function recalculateTotal(WorkOrder $workOrder): void
    {
        $total = $workOrder->items()->sum('total_price');
        $workOrder->update(['total_amount' => $total]);
    }
}
