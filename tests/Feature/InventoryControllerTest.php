<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class InventoryControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();

        $this->admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_create_a_product(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.store'), [
                'name' => 'Filtro de aceite',
                'sku' => 'FA-100',
                'type' => 'repuesto_nacional',
                'description' => 'Filtro de aceite genérico',
                'cost_price' => 5000,
                'selling_price' => 9990,
                'physical_stock' => 10,
                'min_stock' => 2,
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Filtro de aceite',
            'sku' => 'FA-100',
        ]);
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['sku' => 'FA-200']);

        $this->actingAs($this->admin)
            ->from(route('inventory.index'))
            ->put(route('inventory.update', ['product' => $product->id]), [
                'name' => 'Filtro de aceite premium',
                'sku' => 'FA-200',
                'type' => 'repuesto_nacional',
                'description' => 'Filtro de aceite premium',
                'cost_price' => 6000,
                'selling_price' => 12000,
                'physical_stock' => 8,
                'min_stock' => 2,
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Filtro de aceite premium',
            'selling_price' => 12000,
        ]);
    }

    public function test_creating_a_product_with_a_duplicate_sku_fails_validation(): void
    {
        Product::factory()->create(['sku' => 'FA-300']);

        $this->actingAs($this->admin)
            ->from(route('inventory.index'))
            ->post(route('inventory.store'), [
                'name' => 'Otro repuesto',
                'sku' => 'FA-300',
                'cost_price' => 1000,
                'selling_price' => 2000,
                'physical_stock' => 1,
                'min_stock' => 1,
            ])
            ->assertSessionHasErrors('sku');
    }
}
