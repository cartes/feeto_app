<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ExampleTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->setUpTenant();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->where('inventoryImportHighlight.eyebrow', 'Nuevo en inventario')
                ->where('inventoryImportHighlight.tag', 'Importación masiva')
                ->where('inventoryImportHighlight.bullets.0', 'Acepta archivos Excel y CSV.'));
    }

    public function test_authenticated_user_can_visit_homepage_without_redirection(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->where('auth.user.id', $user->id));
    }

    public function test_super_admin_can_visit_homepage_without_redirection(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->where('auth.user.id', $user->id));
    }

    public function test_ziggy_public_group_includes_dashboard(): void
    {
        $publicGroup = config('ziggy.groups.public', []);
        $this->assertContains('dashboard', $publicGroup);
    }

    public function test_ziggy_tenant_group_includes_product_categories(): void
    {
        $tenantGroup = config('ziggy.groups.tenant', []);
        $this->assertContains('product-categories.*', $tenantGroup);
    }

    public function test_homepage_lists_top_6_active_tenants_by_activity(): void
    {
        $tenant1 = Tenant::factory()->create([
            'name' => 'Taller Activo 1',
            'slug' => 'taller-activo-1',
            'is_active' => true,
            'status' => 'active',
            'comuna' => 'Providencia',
            'website_url' => 'https://taller1.cl',
        ]);
        $tenant2 = Tenant::factory()->create([
            'name' => 'Taller Activo 2',
            'slug' => 'taller-activo-2',
            'is_active' => true,
            'status' => 'active',
            'comuna' => 'Las Condes',
            'website_url' => 'https://taller2.cl',
        ]);

        // Simular actividad (appointment para tenant2 para que tenga mayor actividad)
        $tenant2->appointments()->create([
            'plate' => 'XY9999',
            'customer_name' => 'Cliente Activo',
            'phone' => '+56999999999',
            'appointment_date' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('tenants')
                ->where('tenants.0.name', 'Taller Activo 2') // Debería ser el primero por tener más actividad (appointments_count = 1 vs 0)
                ->where('tenants.0.comuna', 'Las Condes')
                ->where('tenants.0.website_url', 'https://taller2.cl')
            );
    }
}
