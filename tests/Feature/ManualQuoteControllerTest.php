<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ManualQuoteControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    private Vehicle $vehicle;

    private Product $product;

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

        $this->client = Client::create([
            'name' => 'Cliente Cotizacion Manual',
            'rut' => '22222222-2',
            'phone' => '+56922222222',
            'email' => 'cliente.manual@example.com',
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'plate' => 'MANU01',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $this->product = Product::factory()->create([
            'selling_price' => 15000,
        ]);

        $this->service = Service::create([
            'name' => 'Cambio de aceite',
            'code' => 'SERV-MAN-001',
            'cost_price' => 8000,
            'selling_price' => 20000,
            'estimated_minutes' => 30,
            'is_active' => true,
        ]);
    }

    private function createDraftQuote(): Quote
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), [
                'client_id' => $this->client->id,
                'vehicle_id' => $this->vehicle->id,
            ])
            ->assertRedirect();

        return Quote::query()->where('client_id', $this->client->id)->latest('id')->firstOrFail();
    }

    public function test_admin_can_create_a_manual_quote_draft(): void
    {
        $quote = $this->createDraftQuote();

        $this->assertSame(Quote::STATUS_DRAFT, $quote->status);
        $this->assertSame($this->client->id, $quote->client_id);
        $this->assertSame($this->vehicle->id, $quote->vehicle_id);
        $this->assertNull($quote->work_order_id);
    }

    public function test_create_page_exposes_vehicle_catalog_brands(): void
    {
        VehicleBrand::query()->create(['name' => 'Toyota', 'code' => 'TOYOTA']);

        $this->actingAs($this->admin)
            ->get(route('quotes.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Quotes/Create')
                ->has('vehicleCatalogBrands', 1)
                ->where('vehicleCatalogBrands.0.name', 'Toyota')
            );
    }

    public function test_store_client_with_vehicle_uses_catalog_names_when_ids_are_sent(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'code' => 'TOYOTA']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Yaris',
            'code' => 'YARIS',
        ]);

        $this->actingAs($this->admin)
            ->post(route('quotes.store-client'), [
                'plate' => 'BBFL45',
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $model->id,
                'vehicle_brand' => 'marca alterada',
                'vehicle_model' => 'modelo alterado',
                'rut' => '11111111-1',
                'name' => 'Cliente nuevo',
                'phone' => '+56911111111',
                'secondary_phone' => '',
                'address' => '',
            ])
            ->assertOk()
            ->assertJsonPath('vehicle.brand', 'Toyota')
            ->assertJsonPath('vehicle.model', 'Yaris');
    }

    public function test_admin_can_add_and_remove_items_from_a_manual_quote(): void
    {
        $quote = $this->createDraftQuote();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'service_id' => $this->service->id,
            'description' => $this->service->name,
        ]);

        $this->assertSame('20000.00', $quote->refresh()->subtotal_amount);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'item_added',
        ]);

        $item = $quote->items()->where('service_id', $this->service->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'product_id' => $this->product->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->product->selling_price,
            ])
            ->assertRedirect();

        $this->assertSame('35000.00', $quote->refresh()->subtotal_amount);

        $this->actingAs($this->admin)
            ->delete(route('quotes.items.destroy', ['quote' => $quote->id, 'item' => $item->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('quote_items', ['id' => $item->id]);
        $this->assertSame('15000.00', $quote->refresh()->subtotal_amount);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'item_removed',
        ]);
    }

    public function test_admin_can_send_a_manual_quote_to_the_customer(): void
    {
        $quote = $this->createDraftQuote();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('quotes.send', ['quote' => $quote->id]), [
                'channel' => 'whatsapp',
            ])
            ->assertRedirect();

        $quote->refresh();

        $this->assertSame(Quote::STATUS_PENDING_CUSTOMER, $quote->status);
        $this->assertNotNull($quote->sent_at);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'quote_sent',
        ]);
    }

    public function test_admin_can_approve_a_manual_quote_and_generate_a_work_order(): void
    {
        $quote = $this->createDraftQuote();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('quotes.approve-manually', ['quote' => $quote->id]), [
                'channel' => 'phone',
            ])
            ->assertRedirect();

        $quote->refresh();

        $this->assertSame(Quote::STATUS_ACCEPTED, $quote->status);
        $this->assertNotNull($quote->work_order_id);

        $workOrder = WorkOrder::query()->findOrFail($quote->work_order_id);
        $this->assertSame(WorkOrder::STATUS_RECEPCION, $workOrder->status);
        $this->assertSame($this->vehicle->id, $workOrder->vehicle_id);

        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'staff_approved_manually',
        ]);
    }

    public function test_customer_can_accept_a_manual_quote_publicly_and_generate_a_work_order(): void
    {
        $quote = $this->createDraftQuote();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('quotes.send', ['quote' => $quote->id]), [
                'channel' => 'whatsapp',
            ])
            ->assertRedirect();

        $this->post(route('quotes.public.respond', ['uuid' => $quote->uuid]), [
            'decision' => 'accepted',
            'notes' => 'De acuerdo, procedan.',
        ])->assertRedirect();

        $quote->refresh();

        $this->assertSame(Quote::STATUS_ACCEPTED, $quote->status);
        $this->assertSame('De acuerdo, procedan.', $quote->customer_response_notes);
        $this->assertNotNull($quote->work_order_id);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'customer_accepted',
        ]);
    }

    public function test_customer_can_reject_a_manual_quote_publicly(): void
    {
        $quote = $this->createDraftQuote();

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'service_id' => $this->service->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => $this->service->selling_price,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('quotes.send', ['quote' => $quote->id]), [
                'channel' => 'whatsapp',
            ])
            ->assertRedirect();

        $this->post(route('quotes.public.respond', ['uuid' => $quote->uuid]), [
            'decision' => 'rejected',
            'notes' => 'Necesito cotizar en otro lado.',
        ])->assertRedirect();

        $quote->refresh();

        $this->assertSame(Quote::STATUS_REJECTED, $quote->status);
        $this->assertNull($quote->work_order_id);
        $this->assertDatabaseHas('quote_events', [
            'quote_id' => $quote->id,
            'event_type' => 'customer_rejected',
        ]);
    }
}
