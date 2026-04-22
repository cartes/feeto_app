<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientInvoiceRequest;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use App\Services\WhatsAppGateway;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientInvoiceController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();
        $this->ensureSalesManagementEnabled($tenant);

        $invoices = ClientInvoice::query()
            ->with(['client', 'workOrder.vehicle'])
            ->latest('due_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices->through(fn (ClientInvoice $invoice): array => $this->serializeInvoice($invoice)),
            'summary' => [
                'total_invoices' => ClientInvoice::query()->count(),
                'overdue_invoices' => ClientInvoice::query()
                    ->whereDate('due_at', '<', now()->toDateString())
                    ->where('amount_due', '>', 0)
                    ->count(),
                'amount_due' => (float) ClientInvoice::query()->sum('amount_due'),
            ],
        ]);
    }

    public function show(ClientInvoice $clientInvoice): Response
    {
        $tenant = Tenant::current();
        $this->ensureSalesManagementEnabled($tenant);

        $clientInvoice->load(['client', 'workOrder.vehicle', 'quote']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $this->serializeInvoice($clientInvoice),
        ]);
    }

    public function store(StoreClientInvoiceRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();
        $this->ensureSalesManagementEnabled($tenant);

        $client = Client::query()->findOrFail($request->validated('client_id'));
        $amountTotal = (float) $request->validated('amount_total');
        $amountDue = (float) ($request->validated('amount_due') ?? $amountTotal);

        $invoice = ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'work_order_id' => $request->validated('work_order_id'),
            'quote_id' => $request->validated('quote_id'),
            'invoice_number' => $request->validated('invoice_number') ?: $this->generateInvoiceNumber($tenant),
            'status' => $this->resolveStatus($amountDue, $request->validated('due_at')),
            'amount_total' => $amountTotal,
            'amount_due' => $amountDue,
            'issued_at' => $request->validated('issued_at'),
            'due_at' => $request->validated('due_at'),
            'notes' => $request->validated('notes'),
        ]);

        return redirect()
            ->route('invoices.show', ['tenantBySlug' => $tenant->slug, 'clientInvoice' => $invoice])
            ->with('success', 'Factura registrada correctamente.');
    }

    private function ensureSalesManagementEnabled(?Tenant $tenant): void
    {
        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_SALES_MANAGEMENT)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_SALES_MANAGEMENT));
        }
    }

    private function generateInvoiceNumber(Tenant $tenant): string
    {
        $nextSequence = ClientInvoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->count() + 1;

        return sprintf('FAC-%s-%04d', $tenant->id, $nextSequence);
    }

    private function resolveStatus(float $amountDue, string $dueAt): string
    {
        if ($amountDue <= 0) {
            return ClientInvoice::STATUS_PAID;
        }

        if (now()->toDateString() > $dueAt) {
            return ClientInvoice::STATUS_OVERDUE;
        }

        return ClientInvoice::STATUS_PENDING;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoice(ClientInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'amount_total' => (float) $invoice->amount_total,
            'amount_due' => (float) $invoice->amount_due,
            'issued_at' => $invoice->issued_at?->toDateString(),
            'due_at' => $invoice->due_at?->toDateString(),
            'paid_at' => $invoice->paid_at?->toISOString(),
            'notes' => $invoice->notes,
            'last_whatsapp_reminder_sent_at' => $invoice->last_whatsapp_reminder_sent_at?->toISOString(),
            'whatsapp_reminder_count' => $invoice->whatsapp_reminder_count,
            'client' => $invoice->client ? [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
                'phone' => $invoice->client->phone,
                'email' => $invoice->client->email,
            ] : null,
            'work_order' => $invoice->workOrder ? [
                'id' => $invoice->workOrder->id,
                'plate' => $invoice->workOrder->vehicle?->plate,
            ] : null,
            'manual_whatsapp_url' => $invoice->client?->phone
                ? sprintf(
                    'https://wa.me/%s?text=%s',
                    preg_replace('/\D+/', '', $invoice->client->phone) ?? '',
                    rawurlencode(app(WhatsAppGateway::class)->buildInvoiceReminderMessage($invoice))
                )
                : null,
        ];
    }
}
