<?php

namespace App\Http\Controllers;

use App\Models\QuoteEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TallerDashboardController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

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

        $quoteNotifications = $tenant->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)
            ? QuoteEvent::query()
                ->with('workOrder.vehicle.client')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (QuoteEvent $event) => [
                    'id' => $event->id,
                    'description' => $event->description,
                    'event_type' => $event->event_type,
                    'actor_type' => $event->actor_type,
                    'timestamp' => $event->created_at->toISOString(),
                    'work_order_id' => $event->work_order_id,
                    'plate' => $event->workOrder?->vehicle?->plate ?? 'N/A',
                    'client' => $event->workOrder?->vehicle?->client?->name ?? 'Cliente',
                ])
            : collect();

        return Inertia::render('Dashboard', [
            'initialActivities' => $initialActivities,
            'quoteNotifications' => $quoteNotifications,
            'tenant' => $tenant->only('id', 'name', 'slug'),
        ]);
    }
}
