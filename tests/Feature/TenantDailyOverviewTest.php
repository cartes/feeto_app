<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantDailyOverviewTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        app(TenantSetupService::class)->provisionTenant($this->tenant, $this->admin);
        $this->actingAs($this->admin);
        $this->travelTo(now()->startOfMonth()->addDays(4)->setTime(10, 0));
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    public function test_summary_uses_real_totals_instead_of_limited_recent_activity(): void
    {
        WorkOrder::factory()->count(7)->create(['tenant_id' => $this->tenant->id]);
        WorkOrder::factory()->count(3)->listo()->create(['tenant_id' => $this->tenant->id]);
        Appointment::factory()->count(7)->create(['tenant_id' => $this->tenant->id, 'appointment_date' => now()->addMonth(), 'status' => 'pending']);
        Appointment::factory()->cancelled()->create(['tenant_id' => $this->tenant->id, 'appointment_date' => now()]);
        Appointment::factory()->create(['tenant_id' => $this->tenant->id, 'appointment_date' => now()->addHour(), 'status' => 'arrived']);
        Appointment::factory()->create(['tenant_id' => $this->tenant->id, 'appointment_date' => now()->subDays(2), 'status' => 'pending']);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('dailySummary.0.count', 1)
            ->where('dailySummary.1.count', 7)
            ->where('dailySummary.2.count', 7)
            ->where('dailySummary.3.count', 3)
            ->has('pendingAppointments', 5)
        );
    }

    public function test_summary_and_pending_requests_are_isolated_by_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        WorkOrder::factory()->create(['tenant_id' => $otherTenant->id]);
        Appointment::factory()->create(['tenant_id' => $otherTenant->id, 'appointment_date' => now()->addDay()]);
        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('dailySummary.1.count', 0)
            ->where('dailySummary.2.count', 0)
            ->where('pendingAppointments', [])
        );
    }

    public function test_summary_respects_the_selected_branch(): void
    {
        $main = $this->tenant->mainBranch();
        $other = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        WorkOrder::factory()->create(['tenant_id' => $this->tenant->id, 'branch_id' => $main->id]);
        WorkOrder::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'branch_id' => $other->id]);
        Appointment::factory()->create(['tenant_id' => $this->tenant->id, 'branch_id' => $main->id, 'appointment_date' => now()->addHour()]);
        Appointment::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'branch_id' => $other->id, 'appointment_date' => now()->addHour()]);

        $this->withSession(['active_branch_id' => $main->id])->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('dailySummary.0.count', 1)
            ->where('dailySummary.1.count', 1)
            ->where('dailySummary.2.count', 1)
            ->has('pendingAppointments', 1)
            ->has('appointments', 1)
        );
    }

    public function test_user_without_operational_permissions_does_not_receive_summary_or_pending_requests(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Appointment::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($user);
        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('dailySummary', [])
            ->where('pendingAppointments', [])
        );
    }

    public function test_deleted_orders_do_not_count_in_the_daily_summary(): void
    {
        WorkOrder::factory()->create(['tenant_id' => $this->tenant->id])->delete();
        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('dailySummary.2.count', 0));
    }

    public function test_ready_vehicle_shortcut_filters_orders_and_retains_the_filter(): void
    {
        WorkOrder::factory()->create(['tenant_id' => $this->tenant->id]);
        $ready = WorkOrder::factory()->listo()->create(['tenant_id' => $this->tenant->id]);
        $this->get(route('work-orders.index', ['tenantBySlug' => $this->tenant->slug, 'view' => 'list', 'status' => 'listo']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'listo')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $ready->id)
            ->where('statusOptions', ['recepcion', 'taller', 'aviso_cliente', 'listo'])
            );
    }

    public function test_invalid_status_filter_is_ignored_without_hiding_orders(): void
    {
        WorkOrder::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->get(route('work-orders.index', ['tenantBySlug' => $this->tenant->slug, 'view' => 'list', 'status' => 'invalid']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('filters.status', null)->has('orders.data', 1));
    }

    public function test_global_search_destination_returns_the_matching_order(): void
    {
        $match = WorkOrder::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->get(route('work-orders.index', ['tenantBySlug' => $this->tenant->slug, 'view' => 'list', 'search' => $match->vehicle->plate]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.id', $match->id));
    }

    private function dashboard(): TestResponse
    {
        return $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))->assertOk();
    }
}
