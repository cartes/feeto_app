<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClientInvoice;
use Illuminate\Support\Facades\Log;

class WhatsAppGateway
{
    /**
     * @return array{status: string, recipient: string, message: string}
     */
    public function sendInvoiceReminder(ClientInvoice $invoice): array
    {
        $recipient = preg_replace('/\D+/', '', $invoice->client?->phone ?? '') ?? '';
        $message = $this->buildInvoiceReminderMessage($invoice);

        Log::info('Sending client invoice WhatsApp reminder', [
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'client_id' => $invoice->client_id,
            'recipient' => $recipient,
            'message' => $message,
        ]);

        return [
            'status' => 'queued',
            'recipient' => $recipient,
            'message' => $message,
        ];
    }

    public function buildInvoiceReminderMessage(ClientInvoice $invoice): string
    {
        $dueDate = $invoice->due_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $amountDue = number_format((float) $invoice->amount_due, 0, ',', '.');
        $invoiceNumber = $invoice->invoice_number ?: sprintf('FAC-%d', $invoice->id);

        return "Hola {$invoice->client?->name}, te recordamos que la factura {$invoiceNumber} tiene un saldo pendiente de \${$amountDue} con vencimiento {$dueDate}. Por favor, contáctanos para regularizar tu pago.";
    }
}
