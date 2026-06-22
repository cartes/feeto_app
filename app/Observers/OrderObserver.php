<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\MinimumMarginWarning;
use App\Models\Order;
use App\Services\PlanFeatureService;
use App\Services\StockService;

class OrderObserver
{
    public function __construct(private StockService $stockService) {}

    public function updated(Order $order): void
    {
        if ($order->isDirty('status') && $order->status === 'invoiced') {
            foreach ($order->items as $item) {
                $product = $item->product;

                if ($product->tenant_id !== $order->tenant_id) {
                    continue;
                }

                if ($order->getOriginal('status') === 'pending') {
                    $this->stockService->fulfillOrderItem($product, $item, $order);

                    $tenant = $order->tenant;
                    if ($tenant->hasFeature(PlanFeatureService::FEATURE_ADVANCED_INVENTORY)) {
                        $margin = $item->unit_price - $product->cost_price;
                        $minMargin = $product->cost_price * (float) config('billing.minimum_margin_rate');
                        if ($margin < $minMargin) {
                            event(new MinimumMarginWarning($product, $item, $tenant));
                        }
                    }
                }
            }
        }
    }
}
