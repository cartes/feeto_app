<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RespondToQuoteRequest;
use App\Http\Requests\SendQuoteRequest;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\TenantNotification;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use App\Services\QuoteItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected QuoteItemService $quoteItemService,
    ) {}

    public function send(SendQuoteRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($request, $workOrder);
        $this->ensureCommercialQuotesEnabled(Tenant::current());
        $this->ensureUserCanDeliverQuote($request->user());

        $quote = $this->quoteFor($workOrder);

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

        $channel = $request->validated('channel') ?? 'manual';

        $quote->update([
            'status' => Quote::STATUS_PENDING_CUSTOMER,
            'sent_at' => now(),
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);

        $this->quoteItemService->recordEvent(
            $quote,
            'staff',
            'quote_sent',
            $this->sendEventDescription($channel),
            ['channel' => $channel]
        );

        return back()->with('success', $this->sendSuccessMessage($channel));
    }

    public function notifyReady(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($request, $workOrder);
        $this->ensureCommercialQuotesEnabled(Tenant::current());

        $quote = $this->quoteFor($workOrder);
        $user = $request->user();

        if ($this->canDeliverQuote($user)) {
            return back()->with('info', 'Tu rol ya puede enviar la cotización directamente al cliente.');
        }

        if ($quote->status === Quote::STATUS_PENDING_CUSTOMER) {
            return back()->with('info', 'La cotización ya fue enviada al cliente.');
        }

        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return back()->with('info', 'La cotización ya fue aceptada por el cliente.');
        }

        if (! $quote->items()->exists()) {
            return back()->withErrors([
                'quote' => 'Debes agregar al menos un ítem antes de avisar que la cotización está lista.',
            ]);
        }

        $clientName = $workOrder->vehicle?->client?->name ?? 'Cliente sin registrar';
        $plate = $workOrder->vehicle?->plate ?? 'Sin patente';
        $availableChannels = $this->availableChannels($workOrder);

        TenantNotification::create([
            'tenant_id' => $workOrder->tenant_id,
            'type' => 'quote_ready',
            'title' => 'Cotización lista para envío',
            'body' => "La OT #{$workOrder->id} de {$clientName} ({$plate}) quedó lista para ser enviada al cliente.",
            'data' => [
                'work_order_id' => $workOrder->id,
                'quote_id' => $quote->id,
                'requested_by' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'available_channels' => $availableChannels,
            ],
        ]);

        $this->quoteItemService->recordEvent(
            $quote,
            'staff',
            'quote_ready_for_admin_review',
            'Se avisó al equipo administrador que la cotización quedó lista para envío.',
            [
                'requested_by_user_id' => $user->id,
                'available_channels' => $availableChannels,
            ]
        );

        return back()->with('success', 'Avisamos al administrador para que envíe la cotización al cliente.');
    }

    public function approveManually(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorizeWorkOrderAccess($request, $workOrder);
        $this->ensureCommercialQuotesEnabled(Tenant::current());
        $this->ensureUserCanDeliverQuote($request->user());

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:phone,whatsapp,email,other'],
        ]);

        $quote = $this->quoteFor($workOrder);

        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return back()->withErrors(['quote' => 'La cotización ya fue aceptada.']);
        }

        $quote->update([
            'status' => Quote::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $description = match ($validated['channel']) {
            'phone' => 'Cotización aprobada manualmente. El cliente confirmó por llamada telefónica.',
            'whatsapp' => 'Cotización aprobada manualmente. El cliente confirmó por WhatsApp.',
            'email' => 'Cotización aprobada manualmente. El cliente confirmó por correo electrónico.',
            default => 'Cotización aprobada manualmente por el equipo.',
        };

        $this->quoteItemService->recordEvent($quote, 'staff', 'staff_approved_manually', $description, [
            'channel' => $validated['channel'],
        ]);

        return back()->with('success', 'Cotización aprobada exitosamente.');
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

        $this->quoteItemService->recordEvent($quote, 'customer', 'customer_'.$decision, $description, [
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

    private function ensureCommercialQuotesEnabled(?Tenant $tenant): void
    {
        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }

    private function quoteFor(WorkOrder $workOrder): Quote
    {
        return $workOrder->quote()->firstOrCreate([
            'work_order_id' => $workOrder->id,
        ]);
    }

    private function ensureUserCanDeliverQuote(User $user): void
    {
        if (! $this->canDeliverQuote($user)) {
            abort(403, 'Solo Admin, Supervisor o Jefe pueden enviar la cotización al cliente.');
        }
    }

    private function canDeliverQuote(User $user): bool
    {
        return $user->is_super_admin
            || $user->hasAnyRole(['Admin', 'Supervisor', 'Jefe']);
    }

    /**
     * @return list<string>
     */
    private function availableChannels(WorkOrder $workOrder): array
    {
        $channels = [];
        $client = $workOrder->vehicle?->client;

        if (filled($client?->phone)) {
            $channels[] = 'whatsapp';
        }

        if (filled($client?->email)) {
            $channels[] = 'email';
        }

        return $channels;
    }

    private function sendEventDescription(string $channel): string
    {
        return match ($channel) {
            'whatsapp' => 'Cotización enviada al cliente para su aprobación por WhatsApp.',
            'email' => 'Cotización enviada al cliente para su aprobación por correo electrónico.',
            'both' => 'Cotización enviada al cliente para su aprobación por WhatsApp y correo electrónico.',
            default => 'Cotización enviada al cliente para su aprobación.',
        };
    }

    private function sendSuccessMessage(string $channel): string
    {
        return match ($channel) {
            'whatsapp' => 'Cotización enviada al cliente. Puedes continuar el contacto por WhatsApp.',
            'email' => 'Cotización enviada al cliente. Puedes continuar el contacto por correo.',
            'both' => 'Cotización enviada al cliente. Puedes continuar el contacto por WhatsApp y correo.',
            default => 'Cotización enviada al cliente.',
        };
    }
}
