<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BranchSubTenantTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $tenantSuperAdmin;

    private Branch $branchA;

    private Branch $branchB;

    private User $branchUserA;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();
        if ($tenant->plan) {
            $tenant->plan->update(['max_users' => 10]);
        } else {
            $plan = Plan::factory()->create(['max_users' => 10]);
            $tenant->update(['plan_id' => $plan->id]);
        }

        $this->tenantSuperAdmin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'is_super_admin' => false,
        ]);
        $this->tenantSuperAdmin->assignRole('Admin');

        $this->branchA = Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sucursal Santiago Centro',
            'code' => 'STGO',
            'is_main' => true,
        ]);

        $this->branchB = Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sucursal Las Condes',
            'code' => 'COND',
            'is_main' => false,
        ]);

        $this->branchUserA = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $this->branchA->id,
            'is_super_admin' => false,
        ]);
        $this->branchUserA->assignRole('Admin');
    }

    public function test_tenant_super_admin_can_switch_active_branch_in_session(): void
    {
        $response = $this->actingAs($this->tenantSuperAdmin)
            ->post(route('branches.switch'), [
                'branch_id' => $this->branchA->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($this->branchA->id, session('active_branch_id'));

        // Puede volver a modo 'all' (todas las sucursales)
        $responseAll = $this->actingAs($this->tenantSuperAdmin)
            ->post(route('branches.switch'), [
                'branch_id' => 'all',
            ]);

        $responseAll->assertRedirect();
        $this->assertEquals('all', session('active_branch_id'));
    }

    public function test_branch_user_cannot_switch_active_branch(): void
    {
        $this->actingAs($this->branchUserA)
            ->post(route('branches.switch'), [
                'branch_id' => $this->branchB->id,
            ])
            ->assertForbidden();
    }

    public function test_branch_user_only_sees_work_orders_from_their_branch_in_index(): void
    {
        $vehicleA = Vehicle::factory()->create(['tenant_id' => $this->branchA->tenant_id]);
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $this->branchB->tenant_id]);

        $orderA = WorkOrder::factory()->create([
            'tenant_id' => $this->branchA->tenant_id,
            'branch_id' => $this->branchA->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        $orderB = WorkOrder::factory()->create([
            'tenant_id' => $this->branchB->tenant_id,
            'branch_id' => $this->branchB->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        $this->actingAs($this->branchUserA)
            ->get(route('work-orders.index', ['view' => 'list']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $orderA->id)
            );
    }

    public function test_branch_user_cannot_view_or_update_work_order_from_another_branch(): void
    {
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $this->branchB->tenant_id]);

        $orderB = WorkOrder::factory()->create([
            'tenant_id' => $this->branchB->tenant_id,
            'branch_id' => $this->branchB->id,
            'vehicle_id' => $vehicleB->id,
            'status' => 'recepcion',
        ]);

        // Ver orden de otra sucursal
        $this->actingAs($this->branchUserA)
            ->get(route('work-orders.show', ['workOrder' => $orderB->id]))
            ->assertForbidden();

        // Actualizar estado de orden de otra sucursal
        $this->actingAs($this->branchUserA)
            ->put(route('work-orders.status.update', ['workOrder' => $orderB->id]), [
                'status' => 'diagnostico',
            ])
            ->assertForbidden();
    }

    public function test_tenant_super_admin_sees_all_work_orders_by_default(): void
    {
        $vehicleA = Vehicle::factory()->create(['tenant_id' => $this->branchA->tenant_id]);
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $this->branchB->tenant_id]);

        WorkOrder::factory()->create([
            'tenant_id' => $this->branchA->tenant_id,
            'branch_id' => $this->branchA->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        WorkOrder::factory()->create([
            'tenant_id' => $this->branchB->tenant_id,
            'branch_id' => $this->branchB->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        $this->actingAs($this->tenantSuperAdmin)
            ->get(route('work-orders.index', ['view' => 'list']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 2)
            );
    }

    public function test_tenant_super_admin_sees_filtered_work_orders_when_branch_is_active(): void
    {
        $vehicleA = Vehicle::factory()->create(['tenant_id' => $this->branchA->tenant_id]);
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $this->branchB->tenant_id]);

        $orderA = WorkOrder::factory()->create([
            'tenant_id' => $this->branchA->tenant_id,
            'branch_id' => $this->branchA->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        WorkOrder::factory()->create([
            'tenant_id' => $this->branchB->tenant_id,
            'branch_id' => $this->branchB->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        $this->actingAs($this->tenantSuperAdmin)
            ->withSession(['active_branch_id' => $this->branchA->id])
            ->get(route('work-orders.index', ['view' => 'list']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $orderA->id)
            );
    }

    public function test_creating_user_with_branch_assignment(): void
    {
        $this->actingAs($this->tenantSuperAdmin)
            ->post(route('tenant.users.store'), [
                'name' => 'Mecánico Sucursal B',
                'email' => 'mecanico.b@taller.cl',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Secret1234!',
                'role' => 'Mecanico',
                'branch_id' => $this->branchB->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'mecanico.b@taller.cl',
            'branch_id' => $this->branchB->id,
        ]);
    }

    public function test_branch_user_cannot_delete_user_from_another_branch(): void
    {
        $otherBranchUser = User::factory()->create([
            'tenant_id' => $this->branchB->tenant_id,
            'branch_id' => $this->branchB->id,
        ]);

        $this->actingAs($this->branchUserA)
            ->delete(route('tenant.users.destroy', ['user' => $otherBranchUser->id]))
            ->assertForbidden();
    }

    public function test_provision_tenant_automatically_creates_casa_matriz(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantSetupService::class)->provisionTenant($tenant);

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'name' => 'Casa Matriz',
            'code' => 'MATRIZ',
            'is_main' => true,
        ]);

        $this->assertNotNull($tenant->refresh()->mainBranch());
        $this->assertEquals('Casa Matriz', $tenant->mainBranch()?->name);
    }
}
