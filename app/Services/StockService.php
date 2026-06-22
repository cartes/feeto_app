<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\SafetyStockReached;
use App\Events\StockDepleted;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;

class StockService
{
    public function adjustPhysicalStock(
        Product $product,
        int $quantity,
        StockMovementType $type,
        ?string $notes = null,
        ?Model $reference = null,
        ?int $userId = null,
    ): StockMovement {
        $stockBefore = $product->physical_stock;

        $product->increment('physical_stock', $quantity);
        $product->refresh();

        $movement = StockMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->physical_stock,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'notes' => $notes,
        ]);

        $this->checkStockAlerts($product);

        return $movement;
    }

    public function adjustReservedStock(
        Product $product,
        int $quantity,
        StockMovementType $type,
        ?string $notes = null,
        ?Model $reference = null,
        ?int $userId = null,
    ): StockMovement {
        $stockBefore = $product->reserved_stock;

        $product->increment('reserved_stock', $quantity);
        $product->refresh();

        return StockMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->reserved_stock,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'notes' => $notes,
        ]);
    }

    public function fulfillOrderItem(Product $product, OrderItem $item, Order $order): void
    {
        $this->adjustPhysicalStock(
            $product,
            -$item->quantity,
            StockMovementType::Exit,
            "Despacho OC #{$order->id}",
            $order,
        );

        $this->adjustReservedStock(
            $product,
            -$item->quantity,
            StockMovementType::Release,
            "Liberación reserva OC #{$order->id}",
            $order,
        );
    }

    public function reserveStock(Product $product, OrderItem $item, Order $order): void
    {
        $this->adjustReservedStock(
            $product,
            $item->quantity,
            StockMovementType::Reservation,
            "Reserva para OC #{$order->id}",
            $order,
        );
    }

    public function releaseReservation(Product $product, OrderItem $item, Order $order): void
    {
        $this->adjustReservedStock(
            $product,
            -$item->quantity,
            StockMovementType::Release,
            "Liberación por eliminación de item OC #{$order->id}",
            $order,
        );
    }

    public function recordManualAdjustment(Product $product, int $oldStock, int $newStock): void
    {
        if ($oldStock === $newStock) {
            return;
        }

        $diff = $newStock - $oldStock;

        StockMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => StockMovementType::Adjustment,
            'quantity' => $diff,
            'stock_before' => $oldStock,
            'stock_after' => $newStock,
            'notes' => "Ajuste manual de {$oldStock} a {$newStock}",
        ]);

        $this->checkStockAlerts($product);
    }

    private function checkStockAlerts(Product $product): void
    {
        $tenant = $product->tenant;

        if ($product->physical_stock <= 0) {
            event(new StockDepleted($product, $tenant));
        } elseif (
            $tenant->hasFeature(PlanFeatureService::FEATURE_ADVANCED_INVENTORY)
            && $product->physical_stock <= $product->min_stock
        ) {
            event(new SafetyStockReached($product, $tenant));
        }
    }
}
