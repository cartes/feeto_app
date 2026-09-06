<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
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
                'rut_taller' => '12345678-5',
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
            'rut_taller' => '12345678-5',
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
            'rut_taller' => '12345678-5',
            'domain' => 'manual.tallerflow.test',
        ]);

        $existingUser = User::factory()->create([
            'email' => 'manuel@admin.test',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.store'), [
                'name' => '', // required
                'rut_taller' => '12345678-5', // unique
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

    public function test_tenants_index_defaults_to_sorting_by_most_active_usage_first(): void
    {
        $tenantLow = Tenant::factory()->create(['name' => 'Taller Poco Uso']);
        $tenantHigh = Tenant::factory()->create(['name' => 'Taller Muy Activo']);
        $tenantMid = Tenant::factory()->create(['name' => 'Taller Medio Uso']);

        WorkOrder::factory()->count(4)->create(['tenant_id' => $tenantHigh->id]);
        WorkOrder::factory()->count(2)->create(['tenant_id' => $tenantMid->id]);
        WorkOrder::factory()->count(0)->create(['tenant_id' => $tenantLow->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->where('filters.sort_by', 'usage')
            ->where('filters.sort_direction', 'desc')
            ->where('tenants.data.0.id', $tenantHigh->id)
            ->where('tenants.data.1.id', $tenantMid->id)
            ->where('tenants.data.2.id', $tenantLow->id)
        );
    }

    public function test_tenants_index_allows_custom_column_sorting(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Alpha Motors']);
        $tenantZ = Tenant::factory()->create(['name' => 'Zulu Motors']);

        // Ascending by name
        $responseAsc = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['sort_by' => 'name', 'sort_direction' => 'asc']));

        $responseAsc->assertOk();
        $responseAsc->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->where('filters.sort_by', 'name')
            ->where('filters.sort_direction', 'asc')
            ->where('tenants.data.0.id', $tenantA->id)
            ->where('tenants.data.1.id', $tenantZ->id)
        );

        // Descending by name
        $responseDesc = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['sort_by' => 'name', 'sort_direction' => 'desc']));

        $responseDesc->assertOk();
        $responseDesc->assertInertia(fn ($page) => $page
            ->where('filters.sort_by', 'name')
            ->where('filters.sort_direction', 'desc')
            ->where('tenants.data.0.id', $tenantZ->id)
            ->where('tenants.data.1.id', $tenantA->id)
        );
    }

    public function test_tenants_index_filters_by_search_query(): void
    {
        $tenant1 = Tenant::factory()->create([
            'name' => 'Taller San Cristóbal',
            'rut_taller' => '76123456-7',
            'slug' => 'san-cristobal',
        ]);
        $tenant2 = Tenant::factory()->create([
            'name' => 'Taller Bellavista',
            'rut_taller' => '77987654-3',
            'slug' => 'bellavista',
        ]);

        $adminTenant2 = User::factory()->create([
            'name' => 'Carlos Administrador',
            'email' => 'carlos@bellavista.cl',
            'tenant_id' => $tenant2->id,
        ]);

        // Search by name
        $resName = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['search' => 'San Cristóbal']));

        $resName->assertOk();
        $resName->assertInertia(fn ($page) => $page
            ->where('tenants.total', 1)
            ->where('tenants.data.0.id', $tenant1->id)
        );

        // Search by RUT
        $resRut = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['search' => '76123456-7']));

        $resRut->assertOk();
        $resRut->assertInertia(fn ($page) => $page
            ->where('tenants.total', 1)
            ->where('tenants.data.0.id', $tenant1->id)
        );

        // Search by Admin User Email
        $resEmail = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['search' => 'carlos@bellavista.cl']));

        $resEmail->assertOk();
        $resEmail->assertInertia(fn ($page) => $page
            ->where('tenants.total', 1)
            ->where('tenants.data.0.id', $tenant2->id)
        );
    }

    public function test_tenants_index_supports_configurable_pagination_options(): void
    {
        Tenant::factory()->count(30)->create();

        // Default 25 per page
        $resDefault = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index'));

        $resDefault->assertOk();
        $resDefault->assertInertia(fn ($page) => $page
            ->where('filters.per_page', 25)
            ->where('tenants.per_page', 25)
            ->where('tenants.last_page', 2)
        );

        // 50 per page
        $res50 = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['per_page' => 50]));

        $res50->assertOk();
        $res50->assertInertia(fn ($page) => $page
            ->where('filters.per_page', 50)
            ->where('tenants.per_page', 50)
            ->where('tenants.last_page', 1)
        );

        // 100 per page
        $res100 = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.index', ['per_page' => 100]));

        $res100->assertOk();
        $res100->assertInertia(fn ($page) => $page
            ->where('filters.per_page', 100)
            ->where('tenants.per_page', 100)
        );
    }
}
