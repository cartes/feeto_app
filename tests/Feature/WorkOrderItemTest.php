<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderItemTest extends TestCase
{
    use RefreshDatabase;

    private function createPrerequisites(): array
    {
        $tenant = Tenant::firstOrCreate(
            ['rut_taller' => '12345678-9'],
            [
                'name'       => 'Taller Test',
                'domain'     => 'test.feeto.test',
            ]
        );

        $tenant->makeCurrent();

        $user = User::factory()->create();

        $client = Client::create([
            'name'      => 'Cliente Test',
            'rut'       => '11111111-1',
            'phone'     => '+56911111111',
            'email'     => 'test@example.com',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate'     => 'TEST01',
            'brand'     => 'Toyota',
            'model'     => 'Corolla',
        ]);

        $workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status'     => 'recepcion',
        ]);

        $product = Product::create([
            'name'           => 'Filtro de aceite',
            'sku'            => 'FILT-001',
            'cost_price'     => 5000,
            'selling_price'  => 12000,
            'physical_stock' => 10,
            'min_stock'      => 2,
        ]);

        return compact('tenant', 'user', 'vehicle', 'workOrder', 'product');
    }

    public function test_can_add_labor_item_to_work_order(): void
    {
        ['user' => $user, 'workOrder' => $workOrder] = $this->createPrerequisites();

        $response = $this->actingAs($user)->post(route('work-orders.items.store', $workOrder->id), [
            'product_id'  => null,
            'description' => 'Mano de obra diagnóstico',
            'quantity'    => 1,
            'unit_price'  => 35000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_order_items', [
            'work_order_id' => $workOrder->id,
            'description'   => 'Mano de obra diagnóstico',
        ]);
        
        $workOrder->refresh();
        $this->assertEquals(35000, $workOrder->total_amount);
    }

    public function test_adding_product_item_decrements_stock(): void
    {
        ['user' => $user, 'workOrder' => $workOrder, 'product' => $product] = $this->createPrerequisites();

        $this->actingAs($user)->post(route('work-orders.items.store', $workOrder->id), [
            'product_id'  => $product->id,
            'description' => $product->name,
            'quantity'    => 3,
            'unit_price'  => $product->selling_price,
        ]);

        $product->refresh();
        $this->assertEquals(7, $product->physical_stock); // 10 - 3
    }

    public function test_removing_item_restores_product_stock(): void
    {
        ['user' => $user, 'workOrder' => $workOrder, 'product' => $product] = $this->createPrerequisites();

        $this->actingAs($user)->post(route('work-orders.items.store', $workOrder->id), [
            'product_id'  => $product->id,
            'description' => $product->name,
            'quantity'    => 3,
            'unit_price'  => $product->selling_price,
        ]);

        $item = WorkOrderItem::first();

        $this->actingAs($user)->delete(route('work-orders.items.destroy', [$workOrder->id, $item->id]));

        $product->refresh();
        $this->assertEquals(10, $product->physical_stock); // Stock restored
        
        $this->assertDatabaseMissing('work_order_items', ['id' => $item->id]);
        
        $workOrder->refresh();
        $this->assertEquals(0, $workOrder->total_amount);
    }
}
