<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Tenant;
use App\Services\TenantSetupService;
use Database\Seeders\DefaultServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class DefaultServiceSeederTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_seeder_creates_default_services_for_current_tenant(): void
    {
        $tenant = $this->setUpTenant();

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'code' => 'SRV-ACEITE',
            'name' => 'Cambio de Aceite y Filtro',
        ]);

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'code' => 'SRV-SCANNER',
        ]);

        $serviceCount = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'like', 'SRV-%')
            ->count();

        $this->assertEquals(10, $serviceCount);
    }

    public function test_seeder_does_not_duplicate_services_on_rerun(): void
    {
        $tenant = $this->setUpTenant();

        $seeder = new DefaultServiceSeeder;
        $seeder->run();

        $serviceCount = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'SRV-ACEITE')
            ->count();

        $this->assertEquals(1, $serviceCount);
    }

    public function test_seeder_marks_all_services_as_active(): void
    {
        $tenant = $this->setUpTenant();

        $inactiveCount = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'like', 'SRV-%')
            ->where('is_active', false)
            ->count();

        $this->assertEquals(0, $inactiveCount);
    }

    public function test_provision_tenant_seeds_services_automatically(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['rut_taller' => '98765432-1'],
            [
                'name' => 'Taller Provisión',
                'slug' => 'taller-provision',
                'domain' => 'provision.tallerflow.test',
            ],
        );

        app(TenantSetupService::class)->provisionTenant($tenant);

        $serviceCount = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'like', 'SRV-%')
            ->count();

        $this->assertEquals(10, $serviceCount);
    }
}
