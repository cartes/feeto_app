<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class QuoteWorkflowTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $admin;

    private WorkOrder $workOrder;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();
        $plan = Plan::factory()->create([
            'feature_keys' => [PlanFeatureService::FEATURE_COMMERCIAL_QUOTES],
        ]);
        $tenant->update(['plan_id' => $plan->id]);

        $this->admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->admin->assignRole('Admin');

        $client = Client::create([
            'name' => 'Cliente Cotizacion',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
            'email' => 'cliente@example.com',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'TEST10',
            'brand' => 'Mazda',
            'model' => 'CX-5',
        ]);

        $this->workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_DIAGNOSTICO,
        ]);

        $this->service = Service::create([
            'name' => 'Diagnóstico computarizado',
            'code' => 'SERV-001',
            'cost_price' => 10000,
            'selling_price' => 25000,
            'estimated_minutes' => 45,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_send_quote_after_adding_items(): void
    {
        $this->actingAs($this->admin)
            ->post(route('work-orders.items.store', ['workOrder' => $this->workOrder->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quote_items', [
            'service_id' => $this->service->id,
            'description' => $this->service->name,
        ]);

        $this->actingAs($this->admin)
            ->post(route('work-orders.quote.send', ['workOrder' => $this->workOrder->id]))
            ->assertRedirect();

        $quote = Quote::query()->where('work_order_id', $this->workOrder->id)->firstOrFail();

        $this->assertSame(Quote::STATUS_PENDING_CUSTOMER, $quote->status);
        $this->assertNotNull($quote->sent_at);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'quote_sent',
        ]);
    }

    public function test_client_can_accept_or_reject_quote_from_tracking(): void
    {
        $quote = Quote::create([
            'work_order_id' => $this->workOrder->id,
            'status' => Quote::STATUS_PENDING_CUSTOMER,
            'subtotal_amount' => 25000,
            'sent_at' => now(),
        ]);

        $this->post(route('tracking.quote.respond', ['uuid' => $this->workOrder->uuid]), [
            'decision' => 'accepted',
            'notes' => 'Adelante con el trabajo.',
        ])->assertRedirect();

        $this->assertSame(Quote::STATUS_ACCEPTED, $quote->refresh()->status);
        $this->assertSame('Adelante con el trabajo.', $quote->customer_response_notes);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'customer_accepted',
        ]);

        $quote->update([
            'status' => Quote::STATUS_PENDING_CUSTOMER,
            'customer_response_notes' => null,
            'responded_at' => null,
        ]);

        $this->post(route('tracking.quote.respond', ['uuid' => $this->workOrder->uuid]), [
            'decision' => 'rejected',
            'notes' => 'Necesito una alternativa.',
        ])->assertRedirect();

        $this->assertSame(Quote::STATUS_REJECTED, $quote->refresh()->status);
        $this->assertSame('Necesito una alternativa.', $quote->customer_response_notes);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'customer_rejected',
        ]);
    }

    public function test_quote_actions_are_blocked_for_lower_plan(): void
    {
        $tenant = $this->admin->tenant()->firstOrFail();
        $tenant->update([
            'plan_id' => Plan::factory()->create(['feature_keys' => []])->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('work-orders.items.store', ['workOrder' => $this->workOrder->id]), [
                'service_id' => $this->service->id,
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertForbidden();
    }
}
