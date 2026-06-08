<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteEvent;
use App\Models\QuoteItem;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ManualQuoteService
{
    public function __construct(protected WorkOrderQuoteService $workOrderQuoteService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data): Quote
    {
        return Quote::create([
            'client_id' => $data['client_id'],
            'vehicle_id' => $data['vehicle_id'],
            'notes' => $data['notes'] ?? null,
            'status' => Quote::STATUS_DRAFT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    public function addItem(
        Quote $quote,
        array $validated,
        string $description = 'Se agregó un ítem a la cotización.',
        array $metadata = []
    ): QuoteItem {
        $itemPayload = $this->workOrderQuoteService->buildItemPayload($validated);

        $item = $quote->items()->create($itemPayload);

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($quote);
        $this->recordEvent($quote, 'staff', 'item_added', $description, array_merge([
            'description' => $itemPayload['description'],
            'item_type' => $itemPayload['item_type'],
            'discount_percent' => $itemPayload['discount_percent'],
            'discount_amount' => $itemPayload['discount_amount'],
        ], $metadata));

        return $item->loadMissing(['product', 'service']);
    }

    public function removeItem(
        Quote $quote,
        QuoteItem $item,
        string $description = 'Se eliminó un ítem de la cotización.'
    ): void {
        if ($item->quote_id !== $quote->id) {
            throw new ModelNotFoundException;
        }

        $removedDescription = $item->description;
        $item->delete();

        $this->markQuoteAsDraft($quote);
        $this->recalculateTotal($quote);
        $this->recordEvent($quote, 'staff', 'item_removed', $description, [
            'description' => $removedDescription,
        ]);
    }

    public function send(Quote $quote, string $channel): void
    {
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
            $this->sendEventDescription($channel),
            ['channel' => $channel]
        );
    }

    public function approveManually(Quote $quote, string $channel): WorkOrder
    {
        $quote->update([
            'status' => Quote::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $description = match ($channel) {
            'phone' => 'Cotización aprobada manualmente. El cliente confirmó por llamada telefónica.',
            'whatsapp' => 'Cotización aprobada manualmente. El cliente confirmó por WhatsApp.',
            'email' => 'Cotización aprobada manualmente. El cliente confirmó por correo electrónico.',
            default => 'Cotización aprobada manualmente por el equipo.',
        };

        $this->recordEvent($quote, 'staff', 'staff_approved_manually', $description, [
            'channel' => $channel,
        ]);

        return $this->convertToWorkOrder($quote);
    }

    public function respondPublicly(Quote $quote, string $decision, ?string $notes): ?WorkOrder
    {
        $status = $decision === 'accepted'
            ? Quote::STATUS_ACCEPTED
            : Quote::STATUS_REJECTED;

        $quote->update([
            'status' => $status,
            'responded_at' => now(),
            'customer_response_notes' => $notes,
        ]);

        $description = $status === Quote::STATUS_ACCEPTED
            ? 'El cliente aceptó la cotización.'
            : 'El cliente rechazó la cotización.';

        $this->recordEvent($quote, 'customer', 'customer_'.$decision, $description, [
            'notes' => $notes,
        ]);

        if ($status === Quote::STATUS_ACCEPTED) {
            return $this->convertToWorkOrder($quote);
        }

        return null;
    }

    /**
     * Crea la Orden de Trabajo a partir de una cotización manual aceptada y la enlaza.
     */
    public function convertToWorkOrder(Quote $quote): WorkOrder
    {
        if ($quote->work_order_id) {
            return $quote->workOrder()->firstOrFail();
        }

        $workOrder = WorkOrder::create([
            'vehicle_id' => $quote->vehicle_id,
            'status' => WorkOrder::STATUS_RECEPCION,
            'observations' => 'Creada a partir de una cotización manual aceptada.',
        ]);

        $quote->update(['work_order_id' => $workOrder->id]);

        return $workOrder;
    }

    private function markQuoteAsDraft(Quote $quote): void
    {
        $quote->update([
            'status' => Quote::STATUS_DRAFT,
            'responded_at' => null,
            'customer_response_notes' => null,
        ]);
    }

    private function recalculateTotal(Quote $quote): void
    {
        $total = (float) $quote->items()->sum('total_price');

        $quote->update(['subtotal_amount' => $total]);
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

    private function sendEventDescription(string $channel): string
    {
        return match ($channel) {
            'whatsapp' => 'Cotización enviada al cliente para su aprobación por WhatsApp.',
            'email' => 'Cotización enviada al cliente para su aprobación por correo electrónico.',
            'both' => 'Cotización enviada al cliente para su aprobación por WhatsApp y correo electrónico.',
            default => 'Cotización enviada al cliente para su aprobación.',
        };
    }
}
