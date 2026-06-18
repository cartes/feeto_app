<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class VehicleCatalogControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_models_endpoint_returns_models_for_the_requested_brand(): void
    {
        $tenant = $this->setUpTenant();
        $user = $this->createAdmin($tenant);
        $toyota = VehicleBrand::query()->create(['name' => 'Toyota', 'code' => 'TOYOTA']);
        $mazda = VehicleBrand::query()->create(['name' => 'Mazda', 'code' => 'MAZDA']);

        $corolla = VehicleModel::query()->create([
            'vehicle_brand_id' => $toyota->id,
            'name' => 'Corolla',
            'code' => 'COROLLA',
        ]);
        $hilux = VehicleModel::query()->create([
            'vehicle_brand_id' => $toyota->id,
            'name' => 'Hilux',
            'code' => 'HILUX',
        ]);
        VehicleModel::query()->create([
            'vehicle_brand_id' => $mazda->id,
            'name' => 'CX-5',
            'code' => 'CX5',
        ]);

        $this->actingAs($user)
            ->get(route('vehicle-catalog.models', [
                'tenantBySlug' => $tenant->slug,
                'vehicleBrand' => $toyota->id,
            ]))
            ->assertOk()
            ->assertJson([
                'models' => [
                    ['id' => $corolla->id, 'name' => 'Corolla'],
                    ['id' => $hilux->id, 'name' => 'Hilux'],
                ],
            ])
            ->assertJsonMissing(['name' => 'CX-5']);
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('Admin');

        URL::defaults(['tenantBySlug' => $tenant->slug]);

        return $admin;
    }
}
