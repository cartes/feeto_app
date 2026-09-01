<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MinimumMarginWarning;
use App\Models\TenantNotification;

class PersistMarginAlertNotification
{
    public function handle(MinimumMarginWarning $event): void
    {
        TenantNotification::create([
            'tenant_id' => $event->tenant->id,
            'type' => 'minimum_margin_warning',
            'title' => "Margen bajo: {$event->product->name}",
            'body' => 'El producto fue vendido por debajo del margen mínimo configurado.',
            'data' => [
                'product_id' => $event->product->id,
                'order_item_id' => $event->item->id,
            ],
        ]);
    }
}
