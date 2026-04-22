<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_tenant_update_shares_success_flash_with_inertia(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Original',
            'domain' => 'original.feeto.test',
            'plan' => 'básico',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->followingRedirects()
            ->from(route('admin.tenants.edit', $tenant))
            ->put(route('admin.tenants.update', $tenant), [
                'name' => 'Taller Actualizado',
                'domain' => 'actualizado.feeto.test',
                'plan' => 'profesional',
                'status' => 'active',
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Edit')
            ->where('flash.success', 'Taller actualizado correctamente.')
            ->where('tenant.name', 'Taller Actualizado')
        );
    }

    public function test_tenant_admin_update_shares_success_flash_with_inertia(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Taller',
            'email' => 'admin@taller.test',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->followingRedirects()
            ->from(route('admin.tenants.edit', $tenant))
            ->put(route('admin.tenants.update_admin', $tenant), [
                'name' => 'Admin Editado',
                'email' => 'editado@taller.test',
                'password' => '',
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Edit')
            ->where('flash.success', 'Administrador guardado correctamente.')
            ->where('tenant.users.0.id', $tenantAdmin->id)
        );

        $this->assertDatabaseHas('users', [
            'id' => $tenantAdmin->id,
            'name' => 'Admin Editado',
            'email' => 'editado@taller.test',
        ]);
    }
}
