<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RespondToQuoteRequest;
use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function send(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($request, $workOrder);
        $this->ensureCommercialQuotesEnabled(Tenant::current());

        $quote = $workOrder->quote()->firstOrCreate([
            'work_order_id' => $workOrder->id,
        ]);

        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return back()->withErrors([
                'quote' => 'La cotización ya fue aceptada por el cliente.',
            ]);
        }

        if (! $quote->items()->exists()) {
            return back()->withErrors([
                'quote' => 'Debes agregar al menos un ítem antes de enviar la cotización.',
            ]);
        }

        $quote->update([
            'status' => Quote::STATUS_PENDING_CUSTOMER,
            'sent_at' => now(),
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);

        $this->recordEvent(
            $quote,
            'staff',
            'quote_sent',
            'Cotización enviada al cliente para su aprobación.'
        );

        return back()->with('success', 'Cotización enviada al cliente.');
    }

    public function respond(RespondToQuoteRequest $request, string $uuid): RedirectResponse
    {
        $workOrder = WorkOrder::withoutGlobalScope('tenant')
            ->with('quote')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $tenant = Tenant::query()->find($workOrder->tenant_id);
        $this->ensureCommercialQuotesEnabled($tenant);

        $quote = $workOrder->quote;

        if (! $quote || $quote->status !== Quote::STATUS_PENDING_CUSTOMER) {
            abort(409, 'La cotización no está disponible para respuesta.');
        }

        $decision = $request->validated('decision');
        $status = $decision === 'accepted'
            ? Quote::STATUS_ACCEPTED
            : Quote::STATUS_REJECTED;

        $quote->update([
            'status' => $status,
            'responded_at' => now(),
            'customer_response_notes' => $request->validated('notes'),
        ]);

        $description = $status === Quote::STATUS_ACCEPTED
            ? 'El cliente aceptó la cotización.'
            : 'El cliente rechazó la cotización.';

        $this->recordEvent($quote, 'customer', 'customer_'.$decision, $description, [
            'notes' => $request->validated('notes'),
        ]);

        return back()->with('success', $status === Quote::STATUS_ACCEPTED
            ? 'Cotización aceptada.'
            : 'Cotización rechazada.');
    }

    private function authorizeWorkOrderAccess(Request $request, WorkOrder $workOrder): void
    {
        $user = $request->user();

        if ($user->is_super_admin) {
            return;
        }

        if ($user->tenant_id !== $workOrder->tenant_id) {
            abort(403, 'No tienes permiso para gestionar esta cotización.');
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(
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

    private function ensureCommercialQuotesEnabled(?Tenant $tenant): void
    {
        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }
}
