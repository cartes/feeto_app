<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use App\Services\UfService;
use Inertia\Inertia;
use Inertia\Response;

class ManualQuoteTrackingController extends Controller
{
    public function __construct(protected UfService $ufService) {}

    /**
     * Muestra una cotización manual públicamente por su UUID, para que el cliente la responda.
     */
    public function show(string $uuid): Response
    {
        $quote = Quote::withoutGlobalScope('tenant')
            ->with(['client', 'vehicle', 'items.product', 'items.service'])
            ->where('uuid', $uuid)
            ->whereNotNull('client_id')
            ->firstOrFail();

        return Inertia::render('Quotes/PublicShow', [
            'quote' => $quote,
            'quoteStatuses' => Quote::statuses(),
            'commercialQuotesEnabled' => Tenant::query()
                ->find($quote->tenant_id)
                ?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES) ?? false,
            'uf_value' => $this->ufService->getCurrentValue(),
        ]);
    }
}
