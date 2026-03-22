<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        // When an item is added to a pending order, reserve it
        if ($orderItem->order->status === 'pending') {
            $orderItem->product->increment('reserved_stock', $orderItem->quantity);
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        // If an item is removed from a pending order, unreserve it
        if ($orderItem->order->status === 'pending') {
            $orderItem->product->decrement('reserved_stock', $orderItem->quantity);
        }
    }
}
