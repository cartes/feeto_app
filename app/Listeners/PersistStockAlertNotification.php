<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StockDepleted;
use App\Models\TenantNotification;

class PersistStockAlertNotification
{
    public function handle(object $event): void
    {
        $isDepleted = $event instanceof StockDepleted;

        TenantNotification::create([
            'tenant_id' => $event->tenant->id,
            'type' => $isDepleted ? 'stock_depleted' : 'safety_stock_reached',
            'title' => $isDepleted
                ? "Stock agotado: {$event->product->name}"
                : "Stock mínimo: {$event->product->name}",
            'body' => $isDepleted
                ? 'El producto llegó a 0 unidades disponibles.'
                : "El producto alcanzó su stock mínimo ({$event->product->min_stock} uds.).",
            'data' => ['product_id' => $event->product->id],
        ]);
    }
}
