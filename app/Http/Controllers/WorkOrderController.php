<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderController extends Controller
{
    /**
     * Muestra el Tablero Kanban con todas las órdenes de trabajo del taller.
     */
    public function index()
    {
        // Gracias a TenantAware, se filtra automático por taller.
        $orders = WorkOrder::with('vehicle.client')->get()->groupBy('status');

        $kanban = [
            'recepcion' => $orders->get('recepcion', []),
            'diagnostico' => $orders->get('diagnostico', []),
            'esperando_repuestos' => $orders->get('esperando_repuestos', []),
            'listo' => $orders->get('listo', []),
        ];

        return Inertia::render('WorkOrders/Index', [
            'kanban' => $kanban,
            'tenantId' => Tenant::current() ? Tenant::current()->id : 0,
        ]);
    }

    /**
     * Actualiza el estado de la Orden de Trabajo a través de llamadas asíncronas / Inertia (Drag and Drop).
     */
    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        // Validamos que el estado ingresado es uno de los 4 permitidos
        $validated = $request->validate([
            'status' => 'required|in:recepcion,diagnostico,esperando_repuestos,listo',
        ]);

        $oldStatus = $workOrder->status;
        $workOrder->update(['status' => $validated['status']]);

        // Disparamos el evento para tiempo real
        broadcast(new \App\Events\WorkOrderStatusUpdated(
            $workOrder->load('vehicle'),
            $oldStatus,
            $validated['status']
        ))->toOthers();

        // Retornamos hacia atrás (Inertia re-renderizará la vista sin recargar)
        return back();
    }
}
