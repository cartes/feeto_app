<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TallerDashboardController extends Controller
{
    /**
     * Muestra el dashboard del taller resolviendo el tenant por slug.
     * Requiere que el usuario autenticado pertenezca al tenant solicitado.
     */
    public function __invoke(Request $request, Tenant $tenantBySlug): InertiaResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        // Solo el superadmin o usuarios del mismo tenant pueden acceder
        if (! $user->is_super_admin && $user->tenant_id !== $tenantBySlug->id) {
            abort(403, 'No tienes acceso a este taller.');
        }

        // Establecer el tenant en contexto para que Spatie Multitenancy filtre correctamente
        $tenantBySlug->makeCurrent();

        $initialActivities = WorkOrder::query()
            ->with('vehicle')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'work_order_id' => $order->id,
                'plate' => $order->vehicle->plate ?? 'N/A',
                'vehicle' => ($order->vehicle->brand ?? '').' '.($order->vehicle->model ?? ''),
                'new_status' => $order->status,
                'timestamp' => $order->updated_at->toISOString(),
            ]);

        return Inertia::render('Dashboard', [
            'initialActivities' => $initialActivities,
            'tenant' => $tenantBySlug->only('id', 'name', 'slug'),
        ]);
    }
}
