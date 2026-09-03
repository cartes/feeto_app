<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WorkOrder;

class ManualQuoteService
{
    public function __construct(protected QuoteItemService $quoteItemService) {}

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
        return $this->quoteItemService->addItem($quote, $validated, $description, $metadata);
    }

    public function removeItem(
        Quote $quote,
        QuoteItem $item,
        string $description = 'Se eliminó un ítem de la cotización.'
    ): void {
        $this->quoteItemService->removeItem($quote, $item, $description);
    }

    public function send(Quote $quote, string $channel): void
    {
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

        $this->quoteItemService->recordEvent($quote, 'staff', 'staff_approved_manually', $description, [
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

        $this->quoteItemService->recordEvent($quote, 'customer', 'customer_'.$decision, $description, [
            'notes' => $notes,
        ]);

        if ($status === Quote::STATUS_ACCEPTED) {
            return $this->convertToWorkOrder($quote);
        }

        return null;
    }

    public function updateTax(Quote $quote, bool $applyTax, ?float $taxRate = null): Quote
    {
        return $this->quoteItemService->updateTax($quote, $applyTax, $taxRate);
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
            'total_amount' => $quote->total_amount,
        ]);

        $quote->update(['work_order_id' => $workOrder->id]);

        return $workOrder;
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
