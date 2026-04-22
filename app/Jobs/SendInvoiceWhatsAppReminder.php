<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ClientInvoice;
use App\Services\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceWhatsAppReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $invoiceId) {}

    public function handle(WhatsAppGateway $whatsAppGateway): void
    {
        $invoice = ClientInvoice::withoutGlobalScope('tenant')
            ->with(['client', 'tenant'])
            ->findOrFail($this->invoiceId);

        if (! $invoice->isOverdue()) {
            Log::info('Skipping WhatsApp reminder because invoice is no longer overdue', [
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        $phone = preg_replace('/\D+/', '', $invoice->client?->phone ?? '') ?? '';

        if ($phone === '') {
            Log::warning('Skipping WhatsApp reminder because client has no phone number', [
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
            ]);

            return;
        }

        $whatsAppGateway->sendInvoiceReminder($invoice);
        $invoice->markReminderSent();
    }
}
