<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
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
        $currentPlan = Plan::factory()->create([
            'name' => 'Básico',
            'slug' => 'basico',
            'max_users' => 5,
        ]);
        $newPlan = Plan::factory()->create([
            'name' => 'Profesional',
            'slug' => 'profesional',
            'max_users' => 10,
        ]);

        $tenant = Tenant::factory()->create([
            'name' => 'Taller Original',
            'domain' => 'original.tallerflow.test',
            'plan' => 'basico',
            'plan_type' => 'basico',
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'subscription_ends_at' => '2026-01-15 00:00:00',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->followingRedirects()
            ->from(route('admin.tenants.edit', $tenant))
            ->put(route('admin.tenants.update', $tenant), [
                'name' => 'Taller Actualizado',
                'domain' => 'actualizado.tallerflow.test',
                'plan_id' => $newPlan->id,
                'status' => 'suspended',
                'subscription_ends_at' => '2026-12-31',
                'phone' => '+56 9 1234 5678',
                'seo_address' => 'Av. Apoquindo 1234',
                'comuna' => 'Las Condes',
                'whatsapp_number' => '+56 9 9876 5432',
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Edit')
            ->where('flash.success', 'Taller actualizado correctamente.')
            ->where('tenant.name', 'Taller Actualizado')
            ->where('tenant.plan_id', $newPlan->id)
            ->where('tenant.subscription_ends_at', '2026-12-31')
        );

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'plan_id' => $newPlan->id,
            'plan' => 'profesional',
            'plan_type' => 'profesional',
            'max_users' => 10,
            'status' => 'suspended',
            'is_active' => false,
            'phone' => '+56 9 1234 5678',
            'seo_address' => 'Av. Apoquindo 1234',
            'comuna' => 'Las Condes',
            'whatsapp_number' => '+56 9 9876 5432',
        ]);
    }

    public function test_tenant_edit_page_shares_available_plans_and_trial_end_date(): void
    {
        $freePlan = Plan::factory()->create([
            'name' => 'Gratuito',
            'slug' => 'gratuito',
            'sort_order' => 1,
        ]);
        $proPlan = Plan::factory()->create([
            'name' => 'Profesional',
            'slug' => 'profesional',
            'sort_order' => 2,
        ]);

        $tenant = Tenant::factory()->create([
            'plan_id' => $proPlan->id,
            'plan' => 'profesional',
            'plan_type' => 'profesional',
            'subscription_ends_at' => '2026-09-30 00:00:00',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.edit', $tenant));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Edit')
            ->where('tenant.plan_id', $proPlan->id)
            ->where('tenant.subscription_ends_at', '2026-09-30')
            ->has('plans', 2)
            ->where('plans.0.id', $freePlan->id)
            ->where('plans.1.id', $proPlan->id)
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
                'domain' => 'manual.tallerflow.test',
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
            'domain' => 'manual.tallerflow.test',
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
            'domain' => 'manual.tallerflow.test',
        ]);

        $existingUser = User::factory()->create([
            'email' => 'manuel@admin.test',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.store'), [
                'name' => '', // required
                'rut_taller' => '12.345.678-9', // unique
                'domain' => 'manual.tallerflow.test', // unique
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
