<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\QueueOverdueInvoiceWhatsAppReminders;
use App\Jobs\SendInvoiceWhatsAppReminder;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Tenant;
use App\Services\TenantSetupService;
use App\Services\WhatsAppGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OverdueInvoiceRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_job_queues_whatsapp_reminders_only_for_enterprise_tenants(): void
    {
        Bus::fake();

        [$enterpriseTenant] = $this->createTenantWithOverdueInvoice('empresa', 'FAC-1-0001');
        [$professionalTenant] = $this->createTenantWithOverdueInvoice('profesional', 'FAC-2-0001');

        (new QueueOverdueInvoiceWhatsAppReminders)->handle();

        Bus::assertDispatched(SendInvoiceWhatsAppReminder::class, 1);
        Bus::assertDispatched(SendInvoiceWhatsAppReminder::class, function (SendInvoiceWhatsAppReminder $job) use ($enterpriseTenant): bool {
            $invoice = ClientInvoice::withoutGlobalScope('tenant')->findOrFail($job->invoiceId);

            return $invoice->tenant_id === $enterpriseTenant->id;
        });

        Bus::assertNotDispatched(SendInvoiceWhatsAppReminder::class, function (SendInvoiceWhatsAppReminder $job) use ($professionalTenant): bool {
            $invoice = ClientInvoice::withoutGlobalScope('tenant')->findOrFail($job->invoiceId);

            return $invoice->tenant_id === $professionalTenant->id;
        });
    }

    public function test_send_reminder_job_marks_invoice_as_reminded(): void
    {
        [$tenant, $invoice] = $this->createTenantWithOverdueInvoice('empresa', 'FAC-3-0001');

        $this->mock(WhatsAppGateway::class, function ($mock): void {
            $mock->shouldReceive('sendInvoiceReminder')->once()->andReturn([
                'status' => 'queued',
                'recipient' => '56911111111',
                'message' => 'ok',
            ]);
        });

        (new SendInvoiceWhatsAppReminder($invoice->id))->handle(app(WhatsAppGateway::class));

        $invoice->refresh();

        $this->assertSame(ClientInvoice::STATUS_OVERDUE, $invoice->status);
        $this->assertSame(1, $invoice->whatsapp_reminder_count);
        $this->assertNotNull($invoice->last_whatsapp_reminder_sent_at);
        $this->assertSame($tenant->id, $invoice->tenant_id);
    }

    /**
     * @return array{0: Tenant, 1: ClientInvoice}
     */
    private function createTenantWithOverdueInvoice(string $plan, string $invoiceNumber): array
    {
        $tenant = Tenant::factory()->create([
            'plan' => $plan,
            'plan_type' => $plan,
        ]);

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);
        app(TenantSetupService::class)->provisionTenant($tenant);

        $client = Client::create([
            'name' => 'Cliente Cobranza',
            'rut' => fake()->unique()->numerify('########-#'),
            'phone' => '+56911111111',
            'email' => fake()->safeEmail(),
        ]);

        $invoice = ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_number' => $invoiceNumber,
            'status' => ClientInvoice::STATUS_PENDING,
            'amount_total' => 90000,
            'amount_due' => 90000,
            'issued_at' => now()->subDays(15)->toDateString(),
            'due_at' => now()->subDays(5)->toDateString(),
        ]);

        return [$tenant, $invoice];
    }
}
