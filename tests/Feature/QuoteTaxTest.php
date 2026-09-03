<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Country;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class QuoteTaxTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Client $client;

    private Vehicle $vehicle;

    private Product $product;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->setUpTenant();
        $this->tenant->update(['country' => Country::Chile]);

        $plan = Plan::factory()->create([
            'feature_keys' => [PlanFeatureService::FEATURE_COMMERCIAL_QUOTES],
        ]);
        $this->tenant->update(['plan_id' => $plan->id]);

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->client = Client::create([
            'name' => 'Cliente Impuestos',
            'rut' => '12345678-5',
            'phone' => '+56911223344',
            'email' => 'cliente.tax@example.com',
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'plate' => 'TAX123',
            'brand' => 'Nissan',
            'model' => 'Versa',
        ]);

        $this->product = Product::factory()->create([
            'selling_price' => 10000,
        ]);

        $this->service = Service::create([
            'name' => 'Alineación y balanceo',
            'code' => 'SERV-TAX-01',
            'cost_price' => 5000,
            'selling_price' => 20000,
            'is_active' => true,
        ]);
    }

    public function test_new_quote_defaults_to_tenant_country_tax_settings(): void
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), [
                'client_id' => $this->client->id,
                'vehicle_id' => $this->vehicle->id,
            ]);

        $quote = Quote::query()->where('client_id', $this->client->id)->firstOrFail();

        $this->assertTrue($quote->apply_tax);
        $this->assertSame('19.00', (string) $quote->tax_rate);
        $this->assertSame('IVA', $quote->taxName());
    }

    public function test_adding_and_removing_items_calculates_subtotal_iva_and_total(): void
    {
        $quote = Quote::create([
            'client_id' => $this->client->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
        ]);

        // Agregar producto por $10.000
        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $quote->refresh();
        $this->assertSame('10000.00', (string) $quote->subtotal_amount);
        $this->assertSame('1900.00', (string) $quote->tax_amount);
        $this->assertSame('11900.00', (string) $quote->total_amount);

        // Agregar servicio por $20.000 -> Subtotal: 30.000, IVA 19%: 5.700, Total: 35.700
        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'quantity' => 1,
            ]);

        $quote->refresh();
        $this->assertSame('30000.00', (string) $quote->subtotal_amount);
        $this->assertSame('5700.00', (string) $quote->tax_amount);
        $this->assertSame('35700.00', (string) $quote->total_amount);

        // Eliminar producto -> Subtotal: 20.000, IVA 19%: 3.800, Total: 23.800
        $productItem = $quote->items()->where('product_id', $this->product->id)->firstOrFail();
        $this->actingAs($this->admin)
            ->delete(route('quotes.items.destroy', ['quote' => $quote->id, 'item' => $productItem->id]));

        $quote->refresh();
        $this->assertSame('20000.00', (string) $quote->subtotal_amount);
        $this->assertSame('3800.00', (string) $quote->tax_amount);
        $this->assertSame('23800.00', (string) $quote->total_amount);
    }

    public function test_can_toggle_and_update_tax_rate_on_manual_quote(): void
    {
        $quote = Quote::create([
            'client_id' => $this->client->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'quantity' => 1,
            ]);

        $quote->refresh();
        $this->assertSame('20000.00', (string) $quote->subtotal_amount);
        $this->assertSame('3800.00', (string) $quote->tax_amount);
        $this->assertSame('23800.00', (string) $quote->total_amount);

        // Desactivar impuesto (ej. cliente exento)
        $this->actingAs($this->admin)
            ->patch(route('quotes.tax.update', ['quote' => $quote->id]), [
                'apply_tax' => false,
                'tax_rate' => 19.0,
            ])
            ->assertSessionHasNoErrors();

        $quote->refresh();
        $this->assertFalse($quote->apply_tax);
        $this->assertSame('0.00', (string) $quote->tax_amount);
        $this->assertSame('20000.00', (string) $quote->total_amount);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'tax_updated',
        ]);

        // Cambiar tasa a 16% (internacional / tasa reducida) y activar
        $this->actingAs($this->admin)
            ->patch(route('quotes.tax.update', ['quote' => $quote->id]), [
                'apply_tax' => true,
                'tax_rate' => 16.0,
            ])
            ->assertSessionHasNoErrors();

        $quote->refresh();
        $this->assertTrue($quote->apply_tax);
        $this->assertSame('16.00', (string) $quote->tax_rate);
        $this->assertSame('3200.00', (string) $quote->tax_amount);
        $this->assertSame('23200.00', (string) $quote->total_amount);
    }

    public function test_work_order_quote_tax_syncs_with_work_order_total_amount(): void
    {
        $workOrder = WorkOrder::create([
            'vehicle_id' => $this->vehicle->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        // Agregar servicio por $20.000 a la OT
        $this->actingAs($this->admin)
            ->post(route('work-orders.items.store', ['workOrder' => $workOrder->id]), [
                'service_id' => $this->service->id,
                'quantity' => 1,
            ]);

        $workOrder->refresh();
        $quote = $workOrder->quote;

        $this->assertSame('20000.00', (string) $quote->subtotal_amount);
        $this->assertSame('3800.00', (string) $quote->tax_amount);
        $this->assertSame('23800.00', (string) $quote->total_amount);
        $this->assertEquals(23800.00, (float) $workOrder->total_amount);

        // Actualizar impuesto en la OT a 0% / desactivado
        $this->actingAs($this->admin)
            ->patch(route('work-orders.quote.tax', ['workOrder' => $workOrder->id]), [
                'apply_tax' => false,
                'tax_rate' => 19.0,
            ])
            ->assertSessionHasNoErrors();

        $workOrder->refresh();
        $quote->refresh();

        $this->assertFalse($quote->apply_tax);
        $this->assertSame('0.00', (string) $quote->tax_amount);
        $this->assertSame('20000.00', (string) $quote->total_amount);
        $this->assertEquals(20000.00, (float) $workOrder->total_amount);
    }
}
