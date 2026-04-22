<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\PlanFeatureService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WorkOrderItemTest extends TestCase
{
    use RefreshDatabase;

    private function createPrerequisites(): array
    {
        $tenant = Tenant::firstOrCreate(
            ['rut_taller' => '12345678-9'],
            [
                'name' => 'Taller Test',
                'slug' => 'taller-test',
                'domain' => 'test.feeto.test',
            ]
        );

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);

        app(TenantSetupService::class)->provisionTenant($tenant);
        $tenant->update([
            'plan_id' => Plan::factory()->create([
                'feature_keys' => [PlanFeatureService::FEATURE_COMMERCIAL_QUOTES],
            ])->id,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('Admin');

        $client = Client::create([
            'name' => 'Cliente Test',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
            'email' => 'test@example.com',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'TEST01',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        $workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => 'recepcion',
        ]);

        $product = Product::create([
            'name' => 'Filtro de aceite',
            'sku' => 'FILT-001',
            'cost_price' => 5000,
            'selling_price' => 12000,
            'physical_stock' => 10,
            'min_stock' => 2,
        ]);

        return compact('tenant', 'user', 'vehicle', 'workOrder', 'product');
    }

    public function test_can_add_labor_item_to_work_order(): void
    {
        ['user' => $user, 'workOrder' => $workOrder] = $this->createPrerequisites();

        $response = $this->actingAs($user)->post(route('work-orders.items.store', ['workOrder' => $workOrder->id]), [
            'product_id' => null,
            'description' => 'Mano de obra diagnóstico',
            'quantity' => 1,
            'unit_price' => 35000,
        ]);

        $response->assertRedirect();
        $quote = Quote::query()->where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($quote);
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote?->id,
            'description' => 'Mano de obra diagnóstico',
        ]);

        $workOrder->refresh();
        $this->assertEquals(35000, $workOrder->total_amount);
    }

    public function test_adding_product_item_does_not_decrement_stock_while_quote_is_pending(): void
    {
        ['user' => $user, 'workOrder' => $workOrder, 'product' => $product] = $this->createPrerequisites();

        $this->actingAs($user)->post(route('work-orders.items.store', ['workOrder' => $workOrder->id]), [
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => 3,
            'unit_price' => $product->selling_price,
        ]);

        $product->refresh();
        $this->assertEquals(10, $product->physical_stock);
    }

    public function test_removing_item_keeps_product_stock_unchanged(): void
    {
        ['user' => $user, 'workOrder' => $workOrder, 'product' => $product] = $this->createPrerequisites();

        $this->actingAs($user)->post(route('work-orders.items.store', ['workOrder' => $workOrder->id]), [
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => 3,
            'unit_price' => $product->selling_price,
        ]);

        $quote = Quote::query()->where('work_order_id', $workOrder->id)->firstOrFail();
        $item = $quote->items()->firstOrFail();

        $this->actingAs($user)->delete(route('work-orders.items.destroy', [
            'workOrder' => $workOrder->id,
            'item' => $item->id,
        ]));

        $product->refresh();
        $this->assertEquals(10, $product->physical_stock);

        $this->assertDatabaseMissing('quote_items', ['id' => $item->id]);

        $workOrder->refresh();
        $this->assertEquals(0, $workOrder->total_amount);
    }
}
