<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\MinimumMarginWarning;
use App\Events\SafetyStockReached;
use App\Events\StockDepleted;
use App\Models\Order;
use App\Models\Product;
use App\Services\PlanFeatureService;

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

                // Verify product belongs to the same tenant
                if ($product->tenant_id !== $order->tenant_id) {
                    continue;
                }

                // Only decrement if it was pending (already reserved)
                if ($order->getOriginal('status') === 'pending') {
                    $product->decrement('physical_stock', $item->quantity);
                    $product->decrement('reserved_stock', $item->quantity);
                    $product->refresh();

                    $tenant = $order->tenant;

                    if ($product->physical_stock <= 0) {
                        event(new StockDepleted($product, $tenant));
                    } elseif ($tenant->hasFeature(PlanFeatureService::FEATURE_ADVANCED_INVENTORY) && $product->physical_stock <= $product->min_stock) {
                        event(new SafetyStockReached($product, $tenant));
                    }

                    if ($tenant->hasFeature(PlanFeatureService::FEATURE_ADVANCED_INVENTORY)) {
                        $margin = $item->unit_price - $product->cost_price;
                        $minMargin = $product->cost_price * 0.10; // 10% minimum margin rule
                        if ($margin < $minMargin) {
                            event(new MinimumMarginWarning($product, $item, $tenant));
                        }
                    }
                }
            }
        }
    }
}
