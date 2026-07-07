<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ClientWorkbookImportTest extends TestCase
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

    public function test_admin_can_import_clients_and_vehicles_from_a_workbook(): void
    {
        $otherTenant = $this->createTenant([
            'name' => 'Taller Dos',
            'slug' => 'taller-dos',
            'domain' => 'dos.tallerflow.test',
            'rut_taller' => '98765432-1',
        ]);

        $otherTenant->makeCurrent();
        Client::create([
            'tenant_id' => $otherTenant->id,
            'rut' => '12345678-5',
            'name' => 'Cliente Otro Taller',
        ]);

        $this->tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $this->tenant->slug]);

        $response = $this->actingAs($this->admin)
            ->post(route('clients.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('clientes.csv', implode("\n", [
                    'nombre,rut,telefono,telefono_secundario,email,direccion,patente,marca,modelo,color,vin',
                    'Juan Perez,12.345.678-5,"+56911111111","+56922222222",juan@cliente.cl,"Av. Apoquindo 1234",ABCD12,Toyota,Yaris,Rojo,JT123456789012345',
                ])),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed_rows'] === 1
            && $summary['created_clients'] === 1
            && $summary['created_vehicles'] === 1
            && $summary['error_rows'] === 0);

        $client = Client::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('rut', '12345678-5')
            ->first();

        $this->assertNotNull($client);
        $this->assertSame('Juan Perez', $client->name);
        $this->assertSame('+56922222222', $client->secondary_phone);
        $this->assertDatabaseHas('vehicles', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'plate' => 'ABCD12',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);
        $this->assertDatabaseHas('clients', [
            'tenant_id' => $otherTenant->id,
            'rut' => '12345678-5',
            'name' => 'Cliente Otro Taller',
        ]);
    }

    public function test_import_updates_existing_records_and_collects_row_errors(): void
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'rut' => '12345678-5',
            'name' => 'Juan Antiguo',
            'phone' => '+56900000000',
        ]);

        Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'plate' => 'ABCD12',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('clients.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('clientes.csv', implode("\n", [
                    'nombre,rut,telefono,email,patente,marca,modelo,color,vin',
                    'Juan Actualizado,12345678-5,"+56999999999",juan@nuevo.cl,ABCD12,Toyota,Yaris,Azul,JT123456789012345',
                    ',,,correo@sinrut.cl,ZXCV98,Hyundai,Accent,Plata,JT123456789012346',
                ])),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $response->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed_rows'] === 1
            && $summary['updated_clients'] === 1
            && $summary['updated_vehicles'] === 1
            && $summary['error_rows'] === 1
            && $summary['errors'][0]['row'] === 3);

        $client->refresh();
        $this->assertSame('Juan Actualizado', $client->name);
        $this->assertSame('+56999999999', $client->phone);
        $this->assertSame('juan@nuevo.cl', $client->email);

        $vehicle = Vehicle::query()->where('tenant_id', $this->tenant->id)->where('plate', 'ABCD12')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame('Yaris', $vehicle->model);
        $this->assertSame('Azul', $vehicle->color);
    }

    public function test_user_without_customer_permission_cannot_import_clients(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($user)
            ->post(route('clients.import', ['tenantBySlug' => $this->tenant->slug]), [
                'workbook' => $this->fakeWorkbook('clientes.csv', "nombre,rut\nJuan Perez,12345678-5"),
            ])
            ->assertForbidden();
    }

    private function fakeWorkbook(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTenant(array $attributes): Tenant
    {
        $tenant = Tenant::create($attributes);

        app(TenantSetupService::class)->provisionTenant($tenant);

        return $tenant;
    }
}
