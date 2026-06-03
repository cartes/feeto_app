<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\PatentReaderAgent;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\BoostrService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Ai\Providers\AnthropicProvider;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ReceptionControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_same_plate_can_exist_in_different_tenants(): void
    {
        $tenantA = $this->setUpTenant();
        $clientA = $this->createClient([
            'tenant_id' => $tenantA->id,
            'rut' => '11111111-1',
            'name' => 'Cliente Uno',
        ]);

        Vehicle::create([
            'client_id' => $clientA->id,
            'plate' => 'AA1111',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $tenantB = $this->createTenant([
            'name' => 'Taller Dos',
            'slug' => 'taller-dos',
            'domain' => 'dos.feeto.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenantB->slug]);

        $clientB = $this->createClient([
            'tenant_id' => $tenantB->id,
            'rut' => '33333333-3',
            'name' => 'Cliente Dos',
        ]);

        Vehicle::create([
            'client_id' => $clientB->id,
            'plate' => 'AA1111',
            'brand' => 'Mazda',
            'model' => 'CX5',
        ]);

        $this->assertSame(2, Vehicle::withoutGlobalScope('tenant')->where('plate', 'AA1111')->count());
    }

    public function test_same_client_rut_can_exist_in_different_tenants(): void
    {
        $tenantA = $this->setUpTenant();

        Client::create([
            'tenant_id' => $tenantA->id,
            'rut' => '11111111-1',
            'name' => 'Cliente Uno',
        ]);

        $tenantB = $this->createTenant([
            'name' => 'Taller Dos',
            'slug' => 'taller-dos',
            'domain' => 'dos.feeto.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();

        Client::create([
            'tenant_id' => $tenantB->id,
            'rut' => '11111111-1',
            'name' => 'Cliente Dos',
        ]);

        $this->assertSame(2, Client::withoutGlobalScope('tenant')->where('rut', '11111111-1')->count());
    }

    public function test_store_order_keeps_existing_vehicle_owner_when_reassignment_is_disabled(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $currentOwner = $this->createClient([
            'tenant_id' => $tenant->id,
            'rut' => '11111111-1',
            'name' => 'Pedro Actual',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $currentOwner->id,
            'plate' => 'AA1111',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response = $this->actingAs($admin)->post(route('receptions.store_order', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'plate' => 'AA1111',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'client_name' => 'Maria Nueva',
            'client_rut' => '22222222-2',
            'client_email' => '',
            'client_phone' => '',
            'selected_client_id' => null,
            'reassign_vehicle_owner' => false,
        ]);

        $response->assertRedirect(route('work-orders.index', ['tenantBySlug' => $tenant->slug]));

        $this->assertSame($currentOwner->id, $vehicle->refresh()->client_id);
        $this->assertDatabaseMissing('clients', [
            'tenant_id' => $tenant->id,
            'rut' => '22222222-2',
        ]);
        $this->assertDatabaseCount('work_orders', 1);
        $this->assertSame(WorkOrder::STATUS_RECEPCION, WorkOrder::query()->firstOrFail()->status);
    }

    public function test_store_returns_recognized_plate_without_queue_worker(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();
        $admin = $this->createAdmin($tenant);

        PatentReaderAgent::fake([
            [
                'patente' => 'GKSB78',
                'marca' => 'Toyota',
                'modelo' => 'Hilux',
            ],
        ])->preventStrayPrompts();

        $this->mock(BoostrService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getVehicleData')
                ->once()
                ->with('GKSB78')
                ->andReturn([
                    'marca' => 'Toyota',
                    'modelo' => 'Hilux',
                    'color' => 'Blanco',
                    'nombre_dueno' => 'Pedro Cliente',
                    'rut_dueno' => '11111111-1',
                ]);
        });

        $response = $this->actingAs($admin)->post(route('receptions.store', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('vehicle.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => true,
                'patente' => 'GKSB78',
                'vehicle' => [
                    'brand' => 'Toyota',
                    'model' => 'Hilux',
                    'color' => 'Blanco',
                    'client' => 'Pedro Cliente',
                    'rut' => '11111111-1',
                ],
            ])
            ->assertJsonMissing(['queue' => true]);

        PatentReaderAgent::assertPrompted('Extrae la patente chilena');
    }

    public function test_store_uses_the_configured_image_provider_for_ocr(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();
        $admin = $this->createAdmin($tenant);

        Config::set('ai.default_for_images', 'anthropic');

        PatentReaderAgent::fake([
            [
                'patente' => 'GKSB78',
                'marca' => 'Toyota',
                'modelo' => 'Hilux',
            ],
        ])->preventStrayPrompts();

        $this->mock(BoostrService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getVehicleData')
                ->once()
                ->with('GKSB78')
                ->andReturn([
                    'marca' => 'Toyota',
                    'modelo' => 'Hilux',
                    'color' => 'Blanco',
                    'nombre_dueno' => 'Pedro Cliente',
                    'rut_dueno' => '11111111-1',
                ]);
        });

        $response = $this->actingAs($admin)->post(route('receptions.store', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('vehicle.jpg'),
        ]);

        $response->assertOk();

        PatentReaderAgent::assertPrompted(function ($prompt): bool {
            return $prompt->prompt === 'Extrae la patente chilena'
                && $prompt->provider() instanceof AnthropicProvider;
        });
    }

    public function test_store_logs_invalid_scans_with_context(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();
        $admin = $this->createAdmin($tenant);

        Log::spy();

        PatentReaderAgent::fake([
            [
                'patente' => 'INVALIDA',
                'marca' => 'Toyota',
                'modelo' => 'Hilux',
            ],
        ])->preventStrayPrompts();

        $response = $this->actingAs($admin)->post(route('receptions.store', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('vehicle.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'error' => 'FALLÓ ESCANEO',
            ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($tenant, $admin): bool {
                return $message === 'Reception OCR returned an invalid plate.'
                    && $context['tenant_id'] === $tenant->id
                    && $context['user_id'] === $admin->id
                    && $context['normalized_plate'] === '1NVAL1DA';
            });
    }

    public function test_store_order_can_reassign_vehicle_to_selected_existing_client(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $currentOwner = $this->createClient([
            'tenant_id' => $tenant->id,
            'rut' => '11111111-1',
            'name' => 'Pedro Actual',
        ]);
        $newOwner = $this->createClient([
            'tenant_id' => $tenant->id,
            'rut' => '22222222-2',
            'name' => 'Maria Nueva',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $currentOwner->id,
            'plate' => 'BB2222',
            'brand' => 'Hyundai',
            'model' => 'Accent',
        ]);

        $response = $this->actingAs($admin)->post(route('receptions.store_order', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'plate' => 'BB2222',
            'brand' => 'Hyundai',
            'model' => 'Accent',
            'client_name' => $newOwner->name,
            'client_rut' => $newOwner->rut,
            'client_email' => 'maria@example.com',
            'client_phone' => '+56912345678',
            'selected_client_id' => $newOwner->id,
            'reassign_vehicle_owner' => true,
        ]);

        $response->assertRedirect(route('work-orders.index', ['tenantBySlug' => $tenant->slug]));

        $vehicle->refresh();
        $newOwner->refresh();

        $this->assertSame($newOwner->id, $vehicle->client_id);
        $this->assertSame('maria@example.com', $newOwner->email);
        $this->assertSame('+56912345678', $newOwner->phone);
    }

    public function test_search_clients_only_returns_matches_from_the_current_tenant(): void
    {
        $tenantA = $this->setUpTenant();
        $admin = $this->createAdmin($tenantA);
        $clientA = $this->createClient([
            'tenant_id' => $tenantA->id,
            'rut' => '11111111-1',
            'name' => 'Pedro Taller Uno',
        ]);

        $tenantB = $this->createTenant([
            'name' => 'Taller Dos',
            'slug' => 'taller-dos',
            'domain' => 'dos.feeto.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();

        $this->createClient([
            'tenant_id' => $tenantB->id,
            'rut' => '33333333-3',
            'name' => 'Pedro Taller Dos',
        ]);

        $tenantA->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenantA->slug]);

        $this->actingAs($admin)
            ->get(route('receptions.clients.search', [
                'tenantBySlug' => $tenantA->slug,
                'search' => 'Pedro',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'clients')
            ->assertJsonPath('clients.0.id', $clientA->id)
            ->assertJsonPath('clients.0.name', $clientA->name);
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('Admin');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createClient(array $attributes): Client
    {
        return Client::create(array_merge([
            'rut' => '99999999-9',
            'name' => 'Cliente Test',
            'phone' => null,
            'email' => null,
        ], $attributes));
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
