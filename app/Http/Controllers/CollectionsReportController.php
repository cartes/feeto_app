<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CollectionsReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        $openStatuses = [
            ClientInvoice::STATUS_PENDING,
            ClientInvoice::STATUS_PARTIAL,
            ClientInvoice::STATUS_OVERDUE,
        ];

        $openInvoices = ClientInvoice::query()
            ->with('client')
            ->whereIn('status', $openStatuses)
            ->where('amount_due', '>', 0)
            ->get();

        $overdueInvoices = $openInvoices
            ->filter(fn (ClientInvoice $invoice): bool => $invoice->isOverdue())
            ->values();

        $agingBuckets = collect([
            ['label' => '1 a 30 días', 'range' => [1, 30]],
            ['label' => '31 a 60 días', 'range' => [31, 60]],
            ['label' => '61+ días', 'range' => [61, null]],
        ])->map(function (array $bucket) use ($overdueInvoices): array {
            [$min, $max] = $bucket['range'];

            $items = $overdueInvoices->filter(function (ClientInvoice $invoice) use ($min, $max): bool {
                $daysOverdue = (int) $invoice->due_at->diffInDays(now());

                if ($max === null) {
                    return $daysOverdue >= $min;
                }

                return $daysOverdue >= $min && $daysOverdue <= $max;
            });

            return [
                'label' => $bucket['label'],
                'count' => $items->count(),
                'amount' => (float) $items->sum('amount_due'),
            ];
        })->values();

        $topDebtors = $overdueInvoices
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                /** @var ClientInvoice $invoice */
                $invoice = $group->first();

                return [
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client?->name ?? 'Cliente',
                    'invoice_count' => $group->count(),
                    'amount_due' => (float) $group->sum('amount_due'),
                ];
            })
            ->sortByDesc('amount_due')
            ->take(8)
            ->values();

        $criticalInvoices = $overdueInvoices
            ->sortByDesc(fn (ClientInvoice $invoice): int => (int) $invoice->due_at->diffInDays(now()))
            ->take(10)
            ->map(fn (ClientInvoice $invoice): array => $this->serializeInvoice($invoice))
            ->values();

        $followUpInvoices = $openInvoices
            ->filter(fn (ClientInvoice $invoice): bool => $invoice->whatsapp_reminder_count > 0)
            ->sortByDesc('whatsapp_reminder_count')
            ->take(8)
            ->map(fn (ClientInvoice $invoice): array => $this->serializeInvoice($invoice))
            ->values();

        return Inertia::render('Reports/Collections', [
            'summary' => [
                'total_invoices' => ClientInvoice::query()->count(),
                'open_invoices' => $openInvoices->count(),
                'overdue_invoices' => $overdueInvoices->count(),
                'amount_due' => (float) $openInvoices->sum('amount_due'),
                'overdue_amount' => (float) $overdueInvoices->sum('amount_due'),
                'average_days_overdue' => round((float) $overdueInvoices->avg(fn (ClientInvoice $invoice): int => (int) $invoice->due_at->diffInDays(now())), 1),
            ],
            'agingBuckets' => $agingBuckets,
            'topDebtors' => $topDebtors,
            'criticalInvoices' => $criticalInvoices,
            'followUpInvoices' => $followUpInvoices,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoice(ClientInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name' => $invoice->client?->name ?? 'Cliente',
            'amount_due' => (float) $invoice->amount_due,
            'due_at' => $invoice->due_at?->toDateString(),
            'days_overdue' => $invoice->due_at ? (int) $invoice->due_at->diffInDays(now()) : 0,
            'whatsapp_reminder_count' => (int) $invoice->whatsapp_reminder_count,
        ];
    }
}
