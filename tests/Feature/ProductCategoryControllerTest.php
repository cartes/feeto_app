<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductCategoryControllerTest extends TestCase
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

    public function test_admin_can_create_a_product_category(): void
    {
        $this->actingAs($this->admin)
            ->post(route('product-categories.store'), [
                'name' => 'Filtros',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Filtros',
            'tenant_id' => $this->admin->tenant_id,
        ]);
    }

    public function test_admin_can_update_a_product_category(): void
    {
        $category = ProductCategory::create([
            'name' => 'Filtros Antiguos',
            'slug' => 'filtros-antiguos',
            'tenant_id' => $this->admin->tenant_id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('product-categories.update', ['productCategory' => $category->id]), [
                'name' => 'Filtros Nuevos',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Filtros Nuevos',
        ]);
    }

    public function test_admin_can_delete_a_product_category(): void
    {
        $category = ProductCategory::create([
            'name' => 'Eliminar',
            'slug' => 'eliminar',
            'tenant_id' => $this->admin->tenant_id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('product-categories.destroy', ['productCategory' => $category->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }
}
