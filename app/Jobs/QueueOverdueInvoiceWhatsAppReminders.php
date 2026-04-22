<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ClientInvoice;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueOverdueInvoiceWhatsAppReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $previousTenant = Tenant::current();

        Tenant::query()
            ->where('is_active', true)
            ->each(function (Tenant $tenant): void {
                if (! $tenant->hasFeature(PlanFeatureService::FEATURE_AUTO_WHATSAPP)) {
                    return;
                }

                $tenant->makeCurrent();

                ClientInvoice::query()
                    ->whereIn('status', [
                        ClientInvoice::STATUS_PENDING,
                        ClientInvoice::STATUS_PARTIAL,
                        ClientInvoice::STATUS_OVERDUE,
                    ])
                    ->whereDate('due_at', '<', now()->toDateString())
                    ->where('amount_due', '>', 0)
                    ->where(function ($query): void {
                        $query->whereNull('last_whatsapp_reminder_sent_at')
                            ->orWhereDate('last_whatsapp_reminder_sent_at', '<', now()->toDateString());
                    })
                    ->each(function (ClientInvoice $invoice): void {
                        SendInvoiceWhatsAppReminder::dispatch($invoice->id);
                    });
            });

        if ($previousTenant) {
            $previousTenant->makeCurrent();
        } else {
            Tenant::forgetCurrent();
        }
    }
}
