<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    /** @test */
    public function test_admin_can_access_settings_page(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $response = $this->get(route('taller.settings', ['tenantBySlug' => $tenant->slug]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Settings/Index'));

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_settings_page_contains_required_props(): void
    {
        $plan = Plan::factory()->create(['max_users' => 3]);
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $response = $this->get(route('taller.settings', ['tenantBySlug' => $tenant->slug]));

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('users')
            ->has('branches')
            ->has('roles')
            ->has('planMaxUsers')
            ->has('currentUserCount')
            ->has('canCreateBranch')
        );

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_non_admin_cannot_access_settings_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();

        $user->assignRole('Mecanico');
        $this->actingAs($user);

        $response = $this->get(route('taller.settings', ['tenantBySlug' => $tenant->slug]));

        $response->assertStatus(403);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_recepcionista_cannot_access_settings_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();

        $user->assignRole('Recepcionista');
        $this->actingAs($user);

        $response = $this->get(route('taller.settings', ['tenantBySlug' => $tenant->slug]));

        $response->assertStatus(403);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_settings_shows_only_current_tenant_users(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        User::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Usuario Otro Taller']);

        app(TenantSetupService::class)->provisionTenant($tenantA, $adminA);
        app(TenantSetupService::class)->provisionTenant($tenantB);

        $tenantA->makeCurrent();
        $this->actingAs($adminA);

        $response = $this->get(route('taller.settings', ['tenantBySlug' => $tenantA->slug]));

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->where('users', fn ($users) => collect($users)->every(
                fn ($u) => $u['name'] !== 'Usuario Otro Taller'
            ))
        );

        Tenant::forgetCurrent();
    }
}
