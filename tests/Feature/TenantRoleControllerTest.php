<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    private function createEmpresaTenantWithAdmin(): array
    {
        $plan = Plan::factory()->create([
            'slug' => 'empresa-test',
            'name' => 'Empresa Test',
            'max_users' => 20,
            'feature_keys' => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                PlanFeatureService::FEATURE_CUSTOM_ROLES,
            ],
        ]);

        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'plan' => 'empresa',
            'plan_type' => 'empresa',
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        return [$tenant, $admin];
    }

    private function createBasicTenantWithAdmin(): array
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        return [$tenant, $admin];
    }

    /** @test */
    public function test_empresa_admin_can_list_roles(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.index', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Roles/Index')
                ->has('roles')
                ->where('canManageRoles', true)
        );
    }

    /** @test */
    public function test_basic_plan_admin_can_view_roles_read_only(): void
    {
        [$tenant, $admin] = $this->createBasicTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.index', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Roles/Index')
                ->where('canManageRoles', false)
        );
    }

    /** @test */
    public function test_empresa_admin_can_access_create_role_form(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.create', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Roles/Create')
                ->has('permissionGroups')
        );
    }

    /** @test */
    public function test_basic_plan_admin_gets_403_accessing_create_form(): void
    {
        [$tenant, $admin] = $this->createBasicTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.create', ['tenantBySlug' => $tenant->slug]));

        $response->assertForbidden();
    }

    /** @test */
    public function test_empresa_admin_can_create_custom_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->post(route('taller.roles.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'Auditor',
            'permissions' => ['reports.view', 'financials.view'],
        ]);

        $response->assertRedirect(route('taller.roles.index', ['tenantBySlug' => $tenant->slug]));
        $this->assertDatabaseHas('roles', ['name' => 'Auditor']);

        $tenant->makeCurrent();
        $role = Role::findByName('Auditor');
        $this->assertTrue($role->hasPermissionTo('reports.view'));
        $this->assertTrue($role->hasPermissionTo('financials.view'));
        $this->assertFalse($role->hasPermissionTo('inventory.manage'));
        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_basic_plan_admin_gets_403_on_store(): void
    {
        [$tenant, $admin] = $this->createBasicTenantWithAdmin();

        $this->actingAs($admin);

        $response = $this->post(route('taller.roles.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'CustomRole',
            'permissions' => ['reports.view'],
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function test_role_name_must_be_unique_per_tenant(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $this->actingAs($admin);

        $this->post(route('taller.roles.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'Auditor',
            'permissions' => ['reports.view'],
        ]);

        $response = $this->post(route('taller.roles.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'Auditor',
            'permissions' => ['financials.view'],
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function test_empresa_admin_can_edit_custom_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $tenant->makeCurrent();
        $customRole = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.edit', ['tenantBySlug' => $tenant->slug, 'role' => $customRole->id]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Roles/Edit')
                ->where('role.name', 'Auditor')
        );
    }

    /** @test */
    public function test_empresa_admin_can_update_custom_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $tenant->makeCurrent();
        $customRole = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->put(route('taller.roles.update', ['tenantBySlug' => $tenant->slug, 'role' => $customRole->id]), [
            'name' => 'Auditor Senior',
            'permissions' => ['reports.view'],
        ]);

        $response->assertRedirect(route('taller.roles.index', ['tenantBySlug' => $tenant->slug]));
        $this->assertDatabaseHas('roles', ['name' => 'Auditor Senior']);
    }

    /** @test */
    public function test_cannot_edit_system_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $tenant->makeCurrent();
        $systemRole = Role::findByName('Mecanico');
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->get(route('taller.roles.edit', ['tenantBySlug' => $tenant->slug, 'role' => $systemRole->id]));

        $response->assertForbidden();
    }

    /** @test */
    public function test_empresa_admin_can_delete_custom_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $tenant->makeCurrent();
        $customRole = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->delete(route('taller.roles.destroy', ['tenantBySlug' => $tenant->slug, 'role' => $customRole->id]));

        $response->assertRedirect(route('taller.roles.index', ['tenantBySlug' => $tenant->slug]));
        $this->assertDatabaseMissing('roles', ['name' => 'Temporal']);
    }

    /** @test */
    public function test_cannot_delete_system_role(): void
    {
        [$tenant, $admin] = $this->createEmpresaTenantWithAdmin();

        $tenant->makeCurrent();
        $systemRole = Role::findByName('Admin');
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->delete(route('taller.roles.destroy', ['tenantBySlug' => $tenant->slug, 'role' => $systemRole->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
    }

    /** @test */
    public function test_basic_plan_admin_gets_403_on_delete(): void
    {
        [$tenant, $admin] = $this->createBasicTenantWithAdmin();

        $tenant->makeCurrent();
        $customRole = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);
        Tenant::forgetCurrent();

        $this->actingAs($admin);

        $response = $this->delete(route('taller.roles.destroy', ['tenantBySlug' => $tenant->slug, 'role' => $customRole->id]));

        $response->assertForbidden();
    }
}
