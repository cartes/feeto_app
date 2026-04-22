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

class TenantRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    /** @test */
    public function test_provision_tenant_creates_all_three_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();

        $this->assertTrue(Role::where('name', 'Admin')->exists());
        $this->assertTrue(Role::where('name', 'Recepcionista')->exists());
        $this->assertTrue(Role::where('name', 'Mecanico')->exists());

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_provision_tenant_assigns_admin_role_to_first_user(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();

        $this->assertTrue($admin->hasRole('Admin'));

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_admin_role_has_all_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();

        $this->assertTrue($admin->can('reports.view'));
        $this->assertTrue($admin->can('inventory.manage'));
        $this->assertTrue($admin->can('users.manage'));
        $this->assertTrue($admin->can('appointments.manage'));

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_recepcionista_role_has_correct_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();

        $user->assignRole('Recepcionista');

        $this->assertTrue($user->can('appointments.manage'));
        $this->assertTrue($user->can('customers.manage'));
        $this->assertTrue($user->can('vehicles.manage'));
        $this->assertTrue($user->can('work-orders.view'));
        $this->assertFalse($user->can('inventory.manage'));
        $this->assertFalse($user->can('reports.view'));
        $this->assertFalse($user->can('users.manage'));

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_mecanico_role_has_correct_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();

        $user->assignRole('Mecanico');

        $this->assertTrue($user->can('work-orders.view-own'));
        $this->assertTrue($user->can('work-orders.update-status'));
        $this->assertTrue($user->can('work-orders.manage-items'));
        $this->assertFalse($user->can('work-orders.view'));
        $this->assertFalse($user->can('appointments.manage'));
        $this->assertFalse($user->can('inventory.manage'));

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_roles_are_isolated_per_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        app(TenantSetupService::class)->provisionTenant($tenantA);
        app(TenantSetupService::class)->provisionTenant($tenantB);

        $tenantA->makeCurrent();
        $countA = Role::count();
        Tenant::forgetCurrent();

        $tenantB->makeCurrent();
        $countB = Role::count();
        Tenant::forgetCurrent();

        $this->assertEquals(3, $countA);
        $this->assertEquals(3, $countB);
    }

    /** @test */
    public function test_user_creation_is_blocked_when_plan_limit_reached(): void
    {
        $plan = Plan::factory()->create(['max_users' => 2]);
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();

        $this->actingAs($admin);

        $response = $this
            ->from(route('tenant.users.index', ['tenantBySlug' => $tenant->slug]))
            ->post(route('tenant.users.store', ['tenantBySlug' => $tenant->slug]), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'Recepcionista',
            ]);

        $response->assertSessionHasErrors('email');

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_non_admin_cannot_access_inventory_routes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();

        $user->assignRole('Mecanico');

        $this->actingAs($user);

        $response = $this->get(route('inventory.index', ['tenantBySlug' => $tenant->slug]));

        $response->assertStatus(403);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_admin_can_access_tenant_role_protected_routes_without_preloading_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $this->actingAs($admin);

        $this->get(route('receptions.create', ['tenantBySlug' => $tenant->slug]))
            ->assertOk();

        $this->get(route('inventory.index', ['tenantBySlug' => $tenant->slug]))
            ->assertOk();

        $this->get(route('taller.settings', ['tenantBySlug' => $tenant->slug]))
            ->assertOk();
    }

    /** @test */
    public function test_roles_and_permissions_are_shared_via_inertia_without_preloading_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $this->actingAs($admin);

        $response = $this->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]));

        $response->assertStatus(200);

        $sharedProps = $response->viewData('page')['props'] ?? [];
        $this->assertArrayHasKey('auth', $sharedProps);
        $this->assertArrayHasKey('roles', $sharedProps['auth']['user']);
        $this->assertArrayHasKey('permissions', $sharedProps['auth']['user']);
        $this->assertContains('Admin', $sharedProps['auth']['user']['roles']);
        $this->assertContains('inventory.manage', $sharedProps['auth']['user']['permissions']);
        $this->assertArrayHasKey('tenantContext', $sharedProps);
        $this->assertEquals('gratuito', $sharedProps['tenantContext']['plan']['code']);
        $this->assertEquals([], $sharedProps['tenantContext']['features']);
    }

    /** @test */
    public function test_plan_features_are_shared_via_inertia(): void
    {
        $plan = Plan::factory()->create([
            'slug' => 'profesional-test',
            'name' => 'Profesional Test',
            'max_users' => 10,
            'feature_keys' => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
            ],
        ]);

        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $this->actingAs($admin);

        $response = $this->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]));

        $sharedProps = $response->viewData('page')['props'] ?? [];

        $this->assertEquals('profesional', $sharedProps['tenantContext']['plan']['code']);
        $this->assertEquals(
            [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
            ],
            $sharedProps['tenantContext']['features']
        );
        $this->assertTrue($sharedProps['planAccess'][PlanFeatureService::FEATURE_SALES_MANAGEMENT]);
    }
}
