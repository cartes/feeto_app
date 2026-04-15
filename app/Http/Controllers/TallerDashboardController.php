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
    public function __invoke(Request $request): InertiaResponse|Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403, 'No tienes acceso a este taller.');
        }

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
            'tenant' => $tenant->only('id', 'name', 'slug'),
        ]);
    }
}
