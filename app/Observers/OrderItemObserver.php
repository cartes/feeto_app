<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\StockService;

class OrderItemObserver
{
    public function __construct(private StockService $stockService) {}

    public function created(OrderItem $orderItem): void
    {
        if ($orderItem->order->status === 'pending') {
            $product = $orderItem->product;

            if ($product->tenant_id === $orderItem->order->tenant_id) {
                $this->stockService->reserveStock($product, $orderItem, $orderItem->order);
            }
        }
    }

    public function deleted(OrderItem $orderItem): void
    {
        if ($orderItem->order->status === 'pending') {
            $product = $orderItem->product;

            if ($product->tenant_id === $orderItem->order->tenant_id) {
                $this->stockService->releaseReservation($product, $orderItem, $orderItem->order);
            }
        }
    }
}
