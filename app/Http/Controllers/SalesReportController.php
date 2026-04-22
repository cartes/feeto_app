<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Models\Quote;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
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
        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        return Inertia::render('Reports/Sales', [
            'summary' => [
                'total_quotes' => $quotes->count(),
                'accepted_quotes' => $acceptedQuotes->count(),
                'quoted_amount' => (float) $quotes->sum('subtotal_amount'),
                'accepted_amount' => (float) $acceptedQuotes->sum('subtotal_amount'),
                'overdue_invoices' => $overdueInvoices->count(),
                'overdue_amount' => (float) $overdueInvoices->sum('amount_due'),
            ],
            'recentQuotes' => $quotes->take(10)->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'status' => $quote->status,
                'subtotal_amount' => (float) $quote->subtotal_amount,
                'work_order_id' => $quote->work_order_id,
                'client_name' => $quote->workOrder?->vehicle?->client?->name,
                'plate' => $quote->workOrder?->vehicle?->plate,
                'sent_at' => $quote->sent_at?->toISOString(),
                'responded_at' => $quote->responded_at?->toISOString(),
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
