<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if the status has changed to 'invoiced'
        if ($order->isDirty('status') && $order->status === 'invoiced') {
            foreach ($order->items as $item) {
                $product = $item->product;

                // Only decrement if it was pending (already reserved)
                if ($order->getOriginal('status') === 'pending') {
                    $product->decrement('physical_stock', $item->quantity);
                    $product->decrement('reserved_stock', $item->quantity);
                }
            }
        }
    }
}
