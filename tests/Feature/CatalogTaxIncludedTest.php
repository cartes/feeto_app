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
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class CatalogTaxIncludedTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Client $client;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->setUpTenant();
        $this->tenant->update(['country' => Country::Chile]);

        $plan = Plan::factory()->create([
            'feature_keys' => [
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
            ],
        ]);
        $this->tenant->update(['plan_id' => $plan->id]);

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->client = Client::create([
            'name' => 'Cliente Test',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
            'email' => 'cliente.test@example.com',
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'plate' => 'TAX999',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);
    }

    public function test_can_create_product_with_tax_included_flag(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.store'), [
                'name' => 'Pastillas de freno',
                'sku' => 'SKU-BRK-01',
                'type' => 'repuesto_nacional',
                'cost_price' => 15000,
                'selling_price' => 35700,
                'tax_included' => true,
                'physical_stock' => 10,
                'min_stock' => 2,
            ])
            ->assertRedirect();

        $product = Product::query()->where('sku', 'SKU-BRK-01')->firstOrFail();

        $this->assertTrue($product->tax_included);
        $this->assertSame(30000.0, $product->netSellingPrice(19.0));
        $this->assertSame(35700.0, $product->grossSellingPrice(19.0));
    }

    public function test_can_create_service_with_tax_included_flag(): void
    {
        $this->actingAs($this->admin)
            ->post(route('services.store'), [
                'name' => 'Mantención 10.000 KM',
                'code' => 'SERV-MAN-10K',
                'cost_price' => 20000,
                'selling_price' => 59500,
                'tax_included' => true,
                'estimated_minutes' => 90,
                'is_active' => true,
            ])
            ->assertRedirect();

        $service = Service::query()->where('code', 'SERV-MAN-10K')->firstOrFail();

        $this->assertTrue($service->tax_included);
        $this->assertSame(50000.0, $service->netSellingPrice(19.0));
        $this->assertSame(59500.0, $service->grossSellingPrice(19.0));
    }

    public function test_adding_tax_included_product_to_quote_calculates_net_subtotal_and_exact_gross_total(): void
    {
        $product = Product::create([
            'name' => 'Neumático 205/55R16',
            'sku' => 'SKU-NEU-16',
            'type' => 'repuesto_nacional',
            'cost_price' => 30000,
            'selling_price' => 59500, // Con IVA (Neto: 50.000, IVA: 9.500)
            'tax_included' => true,
            'physical_stock' => 4,
            'min_stock' => 1,
        ]);

        $quote = Quote::create([
            'client_id' => $this->client->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'apply_tax' => true,
            'tax_rate' => 19.0,
        ]);

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $quote->refresh();

        // 2 neumáticos a $50.000 neto cada uno = Subtotal $100.000, IVA 19% = $19.000, Total = $119.000 (2 x $59.500)
        $this->assertSame('100000.00', (string) $quote->subtotal_amount);
        $this->assertSame('19000.00', (string) $quote->tax_amount);
        $this->assertSame('119000.00', (string) $quote->total_amount);
    }

    public function test_can_download_services_import_template(): void
    {
        $this->actingAs($this->admin)
            ->get(route('services.import.template'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=plantilla-servicios.xlsx');
    }

    public function test_can_import_services_from_csv_with_tax_column(): void
    {
        $csvContent = implode("\n", [
            'codigo,nombre,descripcion,costo,precio_venta,impuesto,minutos_estimados,activo',
            'SERV-CSV-01,Cambio de pastillas,Cambio de pastillas delanteras,10000,35700,con_iva,45,si',
            'SERV-CSV-02,Escaneo automotriz,Diagnóstico OBD2,5000,20000,mas_iva,30,si',
        ]);

        $file = UploadedFile::fake()->createWithContent('servicios.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('services.import'), [
                'workbook' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $s1 = Service::query()->where('code', 'SERV-CSV-01')->firstOrFail();
        $this->assertTrue($s1->tax_included);
        $this->assertEquals(35700.00, (float) $s1->selling_price);

        $s2 = Service::query()->where('code', 'SERV-CSV-02')->firstOrFail();
        $this->assertFalse($s2->tax_included);
        $this->assertEquals(20000.00, (float) $s2->selling_price);
    }

    public function test_authorized_user_can_update_product_tax_included(): void
    {
        $product = Product::create([
            'name' => 'Aceite 5W30 Sintético',
            'sku' => 'OIL-5W30-01',
            'type' => 'insumo',
            'cost_price' => 20000,
            'selling_price' => 35000,
            'tax_included' => false,
            'physical_stock' => 15,
            'min_stock' => 3,
        ]);

        $this->actingAs($this->admin)
            ->put(route('inventory.update', ['product' => $product->id]), [
                'name' => 'Aceite 5W30 Sintético',
                'sku' => 'OIL-5W30-01',
                'type' => 'insumo',
                'cost_price' => 20000,
                'selling_price' => 41650,
                'tax_included' => true,
                'physical_stock' => 15,
                'min_stock' => 3,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertTrue($product->tax_included);
        $this->assertSame(41650.0, (float) $product->selling_price);
        $this->assertSame(35000.0, $product->netSellingPrice(19.0));
    }

    public function test_unauthorized_user_cannot_update_catalog_product(): void
    {
        $seller = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $seller->assignRole('Mecanico');

        $product = Product::create([
            'name' => 'Filtro de Aceite',
            'sku' => 'FLT-OIL-01',
            'type' => 'repuesto_nacional',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'tax_included' => false,
            'physical_stock' => 10,
            'min_stock' => 2,
        ]);

        $this->actingAs($seller)
            ->put(route('inventory.update', ['product' => $product->id]), [
                'name' => 'Filtro de Aceite Editado',
                'sku' => 'FLT-OIL-01',
                'type' => 'repuesto_nacional',
                'cost_price' => 5000,
                'selling_price' => 12000,
                'tax_included' => true,
                'physical_stock' => 10,
                'min_stock' => 2,
            ])
            ->assertForbidden();
    }

    public function test_updating_product_in_catalog_automatically_updates_open_quotes_and_totals(): void
    {
        $product = Product::create([
            'name' => 'Aceite Motor Liqui Moly 10W40',
            'sku' => 'ACE-LM-10W40',
            'type' => 'insumo',
            'cost_price' => 20000,
            'selling_price' => 35000,
            'tax_included' => false,
            'physical_stock' => 50,
            'min_stock' => 10,
        ]);

        $quote = Quote::create([
            'client_id' => $this->client->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'apply_tax' => true,
            'tax_rate' => 19.0,
        ]);

        $this->actingAs($this->admin)
            ->post(route('quotes.items.store', ['quote' => $quote->id]), [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $quote->refresh();
        $this->assertSame('35000.00', (string) $quote->subtotal_amount);
        $this->assertSame('6650.00', (string) $quote->tax_amount);
        $this->assertSame('41650.00', (string) $quote->total_amount);

        // Edit product in catalog to 45.000 Con IVA (Bruto) and changed name
        $this->actingAs($this->admin)
            ->put(route('inventory.update', ['product' => $product->id]), [
                'name' => 'Aceite Motor Liqui Moly 10W40 Sintético',
                'sku' => 'ACE-LM-10W40',
                'type' => 'insumo',
                'cost_price' => 20000,
                'selling_price' => 45000,
                'tax_included' => true,
                'physical_stock' => 50,
                'min_stock' => 10,
            ])
            ->assertRedirect();

        $quote->refresh();
        $item = $quote->items()->first();

        $this->assertSame('Aceite Motor Liqui Moly 10W40 Sintético', $item->description);
        $this->assertSame(37815.13, (float) $item->unit_price);
        $this->assertSame('37815.13', (string) $quote->subtotal_amount);
        $this->assertSame('7184.87', (string) $quote->tax_amount);
        $this->assertSame('45000.00', (string) $quote->total_amount);
    }
}
