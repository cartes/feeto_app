<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        $quotes = Quote::query()
            ->with('workOrder.vehicle.client')
            ->latest()
            ->get();

        $acceptedQuotes = $quotes->where('status', Quote::STATUS_ACCEPTED);

        $summary = [
            'total_quotes' => $quotes->count(),
            'pending_quotes' => $quotes->where('status', Quote::STATUS_PENDING_CUSTOMER)->count(),
            'accepted_quotes' => $acceptedQuotes->count(),
            'rejected_quotes' => $quotes->where('status', Quote::STATUS_REJECTED)->count(),
            'draft_quotes' => $quotes->where('status', Quote::STATUS_DRAFT)->count(),
            'quoted_amount' => (float) $quotes->sum('subtotal_amount'),
            'accepted_amount' => (float) $acceptedQuotes->sum('subtotal_amount'),
        ];

        $topClients = $quotes
            ->groupBy(fn (Quote $quote): string => (string) $quote->workOrder?->vehicle?->client?->id)
            ->filter(fn ($group, string $key): bool => $key !== '')
            ->map(function ($group): array {
                $client = $group->first()->workOrder?->vehicle?->client;

                return [
                    'id' => $client?->id,
                    'name' => $client?->name,
                    'quotes' => $group->count(),
                    'accepted_quotes' => $group->where('status', Quote::STATUS_ACCEPTED)->count(),
                    'quoted_amount' => (float) $group->sum('subtotal_amount'),
                ];
            })
            ->sortByDesc('quoted_amount')
            ->take(5)
            ->values();

        $recentQuotes = $quotes->take(10)->map(function (Quote $quote): array {
            return [
                'id' => $quote->id,
                'status' => $quote->status,
                'subtotal_amount' => (float) $quote->subtotal_amount,
                'sent_at' => optional($quote->sent_at)?->toISOString(),
                'responded_at' => optional($quote->responded_at)?->toISOString(),
                'work_order_id' => $quote->work_order_id,
                'client_name' => $quote->workOrder?->vehicle?->client?->name,
                'plate' => $quote->workOrder?->vehicle?->plate,
            ];
        })->values();

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
            'topClients' => $topClients,
            'recentQuotes' => $recentQuotes,
        ]);
    }
}
