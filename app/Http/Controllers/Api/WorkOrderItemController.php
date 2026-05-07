<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddQuoteItemRequest;
use App\Models\QuoteItem;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use App\Services\WorkOrderQuoteService;
use Illuminate\Http\JsonResponse;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderItemController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected WorkOrderQuoteService $workOrderQuoteService,
    ) {}

    public function store(AddQuoteItemRequest $request, WorkOrder $workOrder): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $item = $this->workOrderQuoteService->addItem($workOrder, $request->validated());

        return response()->json([
            'message' => 'Ítem agregado con éxito',
            'item' => $item,
            'total_amount' => $workOrder->fresh()->total_amount,
        ]);
    }

    public function destroy(WorkOrder $workOrder, QuoteItem $item): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $this->workOrderQuoteService->removeItem($workOrder, $item);

        return response()->json([
            'message' => 'Item eliminado con éxito',
            'total_amount' => $workOrder->fresh()->total_amount,
        ]);
    }

    private function ensureCommercialQuotesEnabled(): void
    {
        if (! Tenant::current()?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }
}
