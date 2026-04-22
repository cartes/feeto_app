<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\QuoteItem;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderItemController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function store(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quote = $this->quoteFor($workOrder);
        $quantity = (float) $validated['quantity'];

        $item = $quote->items()->create([
            'product_id' => $product->id,
            'service_id' => null,
            'item_type' => QuoteItem::TYPE_PRODUCT,
            'description' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->selling_price,
            'total_price' => (float) $product->selling_price * $quantity,
        ]);

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordEvent($quote, 'staff', 'item_added', 'Se agregó un repuesto a la cotización.');

        return response()->json([
            'message' => 'Producto agregado con éxito',
            'item' => $item->load('product'),
            'total_amount' => $workOrder->fresh()->total_amount,
        ]);
    }

    public function destroy(WorkOrder $workOrder, QuoteItem $item): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $quote = $this->quoteFor($workOrder);

        if ($item->quote_id !== $quote->id) {
            throw new ModelNotFoundException;
        }

        $item->delete();

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($workOrder, $quote);
        $this->recordEvent($quote, 'staff', 'item_removed', 'Se eliminó un ítem de la cotización.');

        return response()->json([
            'message' => 'Item eliminado con éxito',
            'total_amount' => $workOrder->fresh()->total_amount,
        ]);
    }

    private function quoteFor(WorkOrder $workOrder): Quote
    {
        return $workOrder->quote()->firstOrCreate([
            'work_order_id' => $workOrder->id,
        ]);
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

    private function recordEvent(Quote $quote, string $actorType, string $eventType, string $description): void
    {
        QuoteEvent::create([
            'tenant_id' => $quote->tenant_id,
            'work_order_id' => $quote->work_order_id,
            'quote_id' => $quote->id,
            'actor_type' => $actorType,
            'event_type' => $eventType,
            'description' => $description,
        ]);
    }

    private function ensureCommercialQuotesEnabled(): void
    {
        if (! Tenant::current()?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }
}
