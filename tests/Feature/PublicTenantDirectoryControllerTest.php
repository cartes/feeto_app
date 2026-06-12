<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicTenantDirectoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_directory_lists_only_active_tenants_when_no_comuna_filter_is_present(): void
    {
        Tenant::factory()->create([
            'name' => 'Taller Centro',
            'slug' => 'taller-centro',
            'domain' => 'taller-centro.tallerflow.cl',
        ]);

        Tenant::factory()->create([
            'name' => 'Taller Norte',
            'slug' => 'taller-norte',
            'domain' => 'taller-norte.tallerflow.cl',
            'comuna' => 'Providencia',
        ]);

        Tenant::factory()->create([
            'name' => 'Taller Suspendido',
            'slug' => 'taller-suspendido',
            'domain' => 'taller-suspendido.tallerflow.cl',
            'status' => 'suspended',
        ]);

        $response = $this->get(route('talleres.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/TenantDirectory')
            ->where('filters.comuna', null)
            ->where('filters.fallback_to_all', false)
            ->has('tenants', 2)
            ->where('tenants.0.name', 'Taller Centro')
            ->where('tenants.1.name', 'Taller Norte')
        );
    }

    public function test_directory_filters_by_comuna_when_verified_location_matches_exist(): void
    {
        Tenant::factory()->create([
            'name' => 'Taller Providencia',
            'slug' => 'taller-providencia',
            'domain' => 'taller-providencia.tallerflow.cl',
            'comuna' => 'Providencia',
        ]);

        Tenant::factory()->create([
            'name' => 'Taller Santiago',
            'slug' => 'taller-santiago',
            'domain' => 'taller-santiago.tallerflow.cl',
            'comuna' => 'Santiago',
        ]);

        $response = $this->get(route('talleres.index', ['comuna' => 'Providencia']));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/TenantDirectory')
            ->where('filters.comuna', 'Providencia')
            ->where('filters.fallback_to_all', false)
            ->has('tenants', 1)
            ->where('tenants.0.name', 'Taller Providencia')
        );
    }

    public function test_directory_falls_back_to_all_tenants_when_requested_comuna_has_no_matches(): void
    {
        Tenant::factory()->create([
            'name' => 'Taller Centro',
            'slug' => 'taller-centro',
            'domain' => 'taller-centro.tallerflow.cl',
        ]);

        Tenant::factory()->create([
            'name' => 'Taller Norte',
            'slug' => 'taller-norte',
            'domain' => 'taller-norte.tallerflow.cl',
            'comuna' => 'Providencia',
        ]);

        Tenant::factory()->create([
            'name' => 'Taller Inactivo',
            'slug' => 'taller-inactivo',
            'domain' => 'taller-inactivo.tallerflow.cl',
            'is_active' => false,
        ]);

        $response = $this->get(route('talleres.index', ['comuna' => 'Santiago']));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/TenantDirectory')
            ->where('filters.comuna', 'Santiago')
            ->where('filters.fallback_to_all', true)
            ->has('tenants', 2)
            ->where('tenants.0.name', 'Taller Centro')
            ->where('tenants.1.name', 'Taller Norte')
        );
    }
}
