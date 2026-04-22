<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class SupervisorReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        $threshold = $tenant->maxDiscountWithoutApproval();

        $discountedItems = QuoteItem::query()
            ->with('quote.workOrder.vehicle.client')
            ->where('discount_percent', '>', 0)
            ->latest()
            ->get();

        $highDiscountItems = $discountedItems
            ->where('discount_percent', '>', $threshold)
            ->values();

        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        return Inertia::render('Reports/Supervisors', [
            'summary' => [
                'discounted_items' => $discountedItems->count(),
                'high_discount_items' => $highDiscountItems->count(),
                'high_discount_amount' => (float) $highDiscountItems->sum('discount_amount'),
                'pending_quotes' => Quote::query()->where('status', Quote::STATUS_PENDING_CUSTOMER)->count(),
                'overdue_invoices' => $overdueInvoices->count(),
            ],
            'discountThreshold' => $threshold,
            'recentDiscounts' => $discountedItems->take(10)->map(fn (QuoteItem $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'work_order_id' => $item->quote?->work_order_id,
                'client_name' => $item->quote?->workOrder?->vehicle?->client?->name,
            ])->values(),
            'overdueInvoices' => $overdueInvoices->take(8)->map(fn (ClientInvoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name ?? 'Cliente',
                'amount_due' => (float) $invoice->amount_due,
                'due_at' => $invoice->due_at?->toDateString(),
            ])->values(),
        ]);
    }
}
