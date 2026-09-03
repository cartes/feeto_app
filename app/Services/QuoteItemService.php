<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\QuoteItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Encapsula las operaciones sobre los ítems y eventos de una cotización,
 * compartidas entre cotizaciones manuales y cotizaciones de Orden de Trabajo.
 */
class QuoteItemService
{
    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    public function addItem(
        Quote $quote,
        array $validated,
        string $description = 'Se agregó un ítem a la cotización.',
        array $metadata = []
    ): QuoteItem {
        $itemPayload = $this->buildItemPayload($validated, $quote);

        $item = $quote->items()->create($itemPayload);

        $this->markQuoteAsDraft($quote);
        $this->recalculateQuoteTotal($quote);
        $this->recordEvent($quote, 'staff', 'item_added', $description, array_merge([
            'description' => $itemPayload['description'],
            'item_type' => $itemPayload['item_type'],
            'discount_percent' => $itemPayload['discount_percent'],
            'discount_amount' => $itemPayload['discount_amount'],
        ], $metadata));

        return $item->loadMissing(['product', 'service']);
    }

    public function removeItem(
        Quote $quote,
        QuoteItem $item,
        string $description = 'Se eliminó un ítem de la cotización.'
    ): void {
        if ($item->quote_id !== $quote->id) {
            throw new ModelNotFoundException;
        }

        $removedDescription = $item->description;
        $item->delete();

        $this->markQuoteAsDraft($quote);
        $this->recalculateQuoteTotal($quote);
        $this->recordEvent($quote, 'staff', 'item_removed', $description, [
            'description' => $removedDescription,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function buildItemPayload(array $validated, ?Quote $quote = null): array
    {
        $product = null;
        $service = null;
        $itemType = QuoteItem::TYPE_MANUAL;
        $description = (string) ($validated['description'] ?? '');
        $baseUnitPrice = (float) ($validated['unit_price'] ?? 0);
        $hasCustomUnitPrice = array_key_exists('unit_price', $validated) && $validated['unit_price'] !== null && $validated['unit_price'] !== '';

        if (! empty($validated['product_id'])) {
            $product = Product::query()->findOrFail($validated['product_id']);
            $itemType = QuoteItem::TYPE_PRODUCT;
            $description = $product->name;
            $baseUnitPrice = $hasCustomUnitPrice ? (float) $validated['unit_price'] : (float) $product->selling_price;

            if ($product->tax_included && ($quote?->apply_tax ?? true) && ! $hasCustomUnitPrice) {
                $rate = (float) (($quote?->tax_rate && $quote->tax_rate > 0) ? $quote->tax_rate : ($quote?->tenant?->defaultTaxRate() ?? 19.0));
                $baseUnitPrice = $product->netSellingPrice($rate);
            }
        }

        if (! empty($validated['service_id'])) {
            $service = Service::query()->findOrFail($validated['service_id']);
            $itemType = QuoteItem::TYPE_SERVICE;
            $description = $service->name;
            $baseUnitPrice = $hasCustomUnitPrice ? (float) $validated['unit_price'] : (float) $service->selling_price;

            if ($service->tax_included && ($quote?->apply_tax ?? true) && ! $hasCustomUnitPrice) {
                $rate = (float) (($quote?->tax_rate && $quote->tax_rate > 0) ? $quote->tax_rate : ($quote?->tenant?->defaultTaxRate() ?? 19.0));
                $baseUnitPrice = $service->netSellingPrice($rate);
            }
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

    public function markQuoteAsDraft(Quote $quote): void
    {
        $quote->update([
            'status' => Quote::STATUS_DRAFT,
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);
    }

    /**
     * Recalcula y persiste el subtotal, impuesto y total de la cotización en base a sus ítems.
     */
    public function recalculateQuoteTotal(Quote $quote): float
    {
        $subtotal = round((float) $quote->items()->sum('total_price'), 2);
        $applyTax = (bool) $quote->apply_tax;
        $taxRate = (float) $quote->tax_rate;

        $taxAmount = ($applyTax && $taxRate > 0)
            ? round($subtotal * ($taxRate / 100), 2)
            : 0.0;

        $totalAmount = round($subtotal + $taxAmount, 2);

        $quote->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        if ($quote->work_order_id) {
            $quote->workOrder?->update(['total_amount' => $totalAmount]);
        }

        return $totalAmount;
    }

    /**
     * Actualiza la configuración de impuestos de la cotización y recalcula los totales.
     */
    public function updateTax(Quote $quote, bool $applyTax, ?float $taxRate = null): Quote
    {
        $taxRate = $taxRate !== null
            ? max(0.0, min(100.0, round($taxRate, 2)))
            : (float) $quote->tax_rate;

        $quote->update([
            'apply_tax' => $applyTax,
            'tax_rate' => $taxRate,
        ]);

        $this->recalculateQuoteTotal($quote);

        $taxName = $quote->taxName();
        $description = $applyTax
            ? "Se configuró impuesto {$taxName} al {$taxRate}%."
            : "Se desactivó el impuesto {$taxName} en la cotización.";

        $this->recordEvent($quote, 'staff', 'tax_updated', $description, [
            'apply_tax' => $applyTax,
            'tax_rate' => $taxRate,
            'tax_amount' => $quote->tax_amount,
            'total_amount' => $quote->total_amount,
        ]);

        return $quote->fresh();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordEvent(
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
