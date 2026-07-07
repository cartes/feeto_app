<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductWorkbookImportTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->setUpTenant();
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_import_products_and_create_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('inventory.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('repuestos.csv', implode("\n", [
                    'sku,nombre,categoria,tipo,descripcion,costo,precio_venta,stock,stock_minimo',
                    'SKU-FA-100,Filtro de aceite,Filtros,repuesto_nacional,Filtro para mantencion,5000,9990,12,3',
                ])),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed_rows'] === 1
            && $summary['created_products'] === 1
            && $summary['created_categories'] === 1
            && $summary['error_rows'] === 0);

        $category = ProductCategory::query()->where('tenant_id', $this->tenant->id)->where('name', 'Filtros')->first();
        $this->assertNotNull($category);

        $product = Product::query()->where('tenant_id', $this->tenant->id)->where('sku', 'SKU-FA-100')->first();
        $this->assertNotNull($product);
        $this->assertSame('Filtro de aceite', $product->name);
        $this->assertSame($category->id, $product->category_id);
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'quantity' => 12,
            'stock_before' => 0,
            'stock_after' => 12,
        ]);
    }

    public function test_import_updates_existing_product_and_records_stock_adjustment(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-FA-200',
            'name' => 'Filtro antiguo',
            'physical_stock' => 2,
            'min_stock' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('inventory.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('repuestos.csv', implode("\n", [
                    'sku,nombre,categoria,tipo,descripcion,costo,precio_venta,stock,stock_minimo',
                    'SKU-FA-200,Filtro premium,Filtros,repuesto_internacional,Filtro actualizado,6500,12990,7,2',
                    ',Sin SKU,Filtros,repuesto_nacional,Invalido,1000,2000,1,1',
                ])),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $response->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed_rows'] === 1
            && $summary['updated_products'] === 1
            && $summary['error_rows'] === 1
            && $summary['errors'][0]['row'] === 3);

        $product->refresh();
        $this->assertSame('Filtro premium', $product->name);
        $this->assertSame('repuesto_internacional', $product->type);
        $this->assertSame(7, $product->physical_stock);
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'stock_before' => 2,
            'stock_after' => 7,
        ]);
    }

    public function test_user_without_inventory_permission_cannot_import_products(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($user)
            ->post(route('inventory.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('repuestos.csv', "sku,nombre\nSKU-1,Producto"),
            ])
            ->assertForbidden();
    }

    private function fakeWorkbook(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
