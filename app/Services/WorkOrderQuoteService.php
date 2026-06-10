<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WorkOrder;

class WorkOrderQuoteService
{
    public function __construct(protected QuoteItemService $quoteItemService) {}

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

        $item = $this->quoteItemService->addItem($quote, $validated, $description, $metadata);

        $this->syncWorkOrderTotal($workOrder, $quote);

        return $item;
    }

    public function removeItem(
        WorkOrder $workOrder,
        QuoteItem $item,
        string $description = 'Se eliminó un ítem de la cotización.'
    ): void {
        $quote = $this->quoteFor($workOrder);

        $this->quoteItemService->removeItem($quote, $item, $description);

        $this->syncWorkOrderTotal($workOrder, $quote);
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
        return $this->quoteItemService->buildItemPayload($validated);
    }

    private function syncWorkOrderTotal(WorkOrder $workOrder, Quote $quote): void
    {
        $workOrder->update(['total_amount' => $quote->subtotal_amount]);
    }
}
