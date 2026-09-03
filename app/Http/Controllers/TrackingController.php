<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use App\Services\UfService;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    public function __construct(protected UfService $ufService) {}

    /**
     * Muestra la Orden de Trabajo públicamente por UUID
     */
    public function show(string $uuid): Response
    {
        $workOrder = WorkOrder::withoutGlobalScope('tenant')
            ->with(['vehicle', 'vehicle.client', 'quote.items.product', 'quote.items.service'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $tenant = Tenant::query()->find($workOrder->tenant_id);

        return Inertia::render('Tracking/Show', [
            'workOrder' => $workOrder,
            'taxName' => $workOrder->quote?->taxName() ?? $tenant?->taxName() ?? 'IVA',
            'quoteStatuses' => Quote::statuses(),
            'commercialQuotesEnabled' => $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES) ?? false,
            'uf_value' => $this->ufService->getCurrentValue(),
        ]);
    }
}
