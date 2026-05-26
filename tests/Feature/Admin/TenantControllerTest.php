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

    public function test_superadmin_can_access_tenant_creation_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Create')
        );
    }

    public function test_non_superadmin_cannot_access_tenant_creation_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.tenants.create'));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_create_tenant_with_admin_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.store'), [
                'name' => 'Taller Manuel',
                'rut_taller' => '12.345.678-9',
                'domain' => 'manual.feeto.test',
                'plan' => 'basico',
                'status' => 'active',
                'subscription_ends_at' => '2026-12-31',
                'admin_name' => 'Admin Manuel',
                'admin_email' => 'manuel@admin.test',
                'admin_password' => 'password123',
            ]);

        $response->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'Taller Manuel',
            'rut_taller' => '12.345.678-9',
            'domain' => 'manual.feeto.test',
            'plan' => 'basico',
            'status' => 'active',
        ]);

        $tenant = Tenant::where('name', 'Taller Manuel')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'name' => 'Admin Manuel',
            'email' => 'manuel@admin.test',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_tenant_creation_validates_required_and_unique_fields(): void
    {
        // Create an existing tenant to conflict with unique fields
        $existingTenant = Tenant::factory()->create([
            'rut_taller' => '12.345.678-9',
            'domain' => 'manual.feeto.test',
        ]);

        $existingUser = User::factory()->create([
            'email' => 'manuel@admin.test',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.store'), [
                'name' => '', // required
                'rut_taller' => '12.345.678-9', // unique
                'domain' => 'manual.feeto.test', // unique
                'plan' => 'invalid-plan', // invalid enum
                'status' => 'invalid-status', // invalid in
                'admin_name' => '', // required
                'admin_email' => 'manuel@admin.test', // unique
                'admin_password' => 'short', // min 8
            ]);

        $response->assertSessionHasErrors([
            'name',
            'rut_taller',
            'domain',
            'plan',
            'status',
            'admin_name',
            'admin_email',
            'admin_password',
        ]);
    }
}
