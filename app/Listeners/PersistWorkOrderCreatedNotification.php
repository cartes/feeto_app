<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\WorkOrderDraftCreated;
use App\Models\TenantNotification;

class PersistWorkOrderCreatedNotification
{
    public function handle(WorkOrderDraftCreated $event): void
    {
        TenantNotification::create([
            'tenant_id' => $event->tenantId,
            'type' => 'work_order_created',
            'title' => 'Nueva OT recibida',
            'body' => "Patente {$event->workOrder->plate} — nueva orden de trabajo ingresada al sistema.",
            'data' => ['work_order_id' => $event->workOrder->id],
        ]);
    }
}
