<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WorkOrder;
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
            ->with(['vehicle', 'vehicle.client'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('Tracking/Show', [
            'workOrder' => $workOrder,
        ]);
    }
}
