<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    /**
     * Muestra la Orden de Trabajo públicamente por UUID
     */
    public function show(string $uuid): Response
    {
        $workOrder = WorkOrder::withoutGlobalScope('tenant')
            ->with(['vehicle', 'vehicle.client', 'quote.items.product', 'quote.items.service'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('Tracking/Show', [
            'workOrder' => $workOrder,
            'quoteStatuses' => Quote::statuses(),
            'commercialQuotesEnabled' => Tenant::query()
                ->find($workOrder->tenant_id)
                ?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES) ?? false,
        ]);
    }
}
