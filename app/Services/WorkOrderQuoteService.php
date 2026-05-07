<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WorkOrderQuoteService
{
    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    public function addItem(
        WorkOrder $workOrder,
        array $validated,
        string $description = 'Se agregó un ítem a la cotización.',
        array $metadata = []
    ): QuoteItem {
        $quote = $this->quoteFor($workOrder);
        $itemPayload = $this->buildItemPayload($validated);

        $item = $quote->items()->create($itemPayload);

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordEvent($quote, 'staff', 'item_added', $description, array_merge([
            'description' => $itemPayload['description'],
            'item_type' => $itemPayload['item_type'],
            'discount_percent' => $itemPayload['discount_percent'],
            'discount_amount' => $itemPayload['discount_amount'],
        ], $metadata));

        return $item->loadMissing(['product', 'service']);
    }

    public function removeItem(
        WorkOrder $workOrder,
        QuoteItem $item,
        string $description = 'Se eliminó un ítem de la cotización.'
    ): void {
        $quote = $this->quoteFor($workOrder);

        if ($item->quote_id !== $quote->id) {
            throw new ModelNotFoundException;
        }

        $removedDescription = $item->description;
        $item->delete();

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordEvent($quote, 'staff', 'item_removed', $description, [
            'description' => $removedDescription,
        ]);
    }

    public function quoteFor(WorkOrder $workOrder): Quote
    {
        return $workOrder->quote()->firstOrCreate([
            'work_order_id' => $workOrder->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function buildItemPayload(array $validated): array
    {
        $product = null;
        $service = null;
        $itemType = QuoteItem::TYPE_MANUAL;
        $description = (string) ($validated['description'] ?? '');
        $baseUnitPrice = (float) ($validated['unit_price'] ?? 0);

        if (! empty($validated['product_id'])) {
            $product = Product::query()->findOrFail($validated['product_id']);
            $itemType = QuoteItem::TYPE_PRODUCT;
            $description = $product->name;
            $baseUnitPrice = (float) $product->selling_price;
        }

        if (! empty($validated['service_id'])) {
            $service = Service::query()->findOrFail($validated['service_id']);
            $itemType = QuoteItem::TYPE_SERVICE;
            $description = $service->name;
            $baseUnitPrice = (float) $service->selling_price;
        }

        $quantity = (float) $validated['quantity'];
        $discountPercent = round((float) ($validated['discount_percent'] ?? 0), 2);
        $discountMultiplier = max(0, 1 - ($discountPercent / 100));
        $discountedUnitPrice = round($baseUnitPrice * $discountMultiplier, 2);
        $discountAmount = round(($baseUnitPrice - $discountedUnitPrice) * $quantity, 2);

        return [
            'product_id' => $product?->id,
            'service_id' => $service?->id,
            'item_type' => $itemType,
            'description' => $description,
            'quantity' => $quantity,
            'original_unit_price' => $baseUnitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'unit_price' => $discountedUnitPrice,
            'total_price' => round($quantity * $discountedUnitPrice, 2),
        ];
    }

    private function markQuoteAsDraft(Quote $quote): void
    {
        $quote->update([
            'status' => Quote::STATUS_DRAFT,
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);
    }

    private function recalculateTotal(WorkOrder $workOrder, Quote $quote): void
    {
        $total = (float) $quote->items()->sum('total_price');

        $quote->update(['subtotal_amount' => $total]);
        $workOrder->update(['total_amount' => $total]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(
        Quote $quote,
        string $actorType,
        string $eventType,
        string $description,
        array $metadata = []
    ): void {
        QuoteEvent::create([
            'tenant_id' => $quote->tenant_id,
            'work_order_id' => $quote->work_order_id,
            'quote_id' => $quote->id,
            'actor_type' => $actorType,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
