<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\LoginLog;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AdminDashboardService;
    }

    public function test_get_stats_counts_tenants_users_activity_and_revenue(): void
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

        $activeTenant = Tenant::factory()->create();
        $inactiveTenant = Tenant::factory()->create(['is_active' => false]);
        $expiredTenant = Tenant::factory()->create(['subscription_ends_at' => $now->copy()->subDay()]);

        $user = User::factory()->create(['tenant_id' => $activeTenant->id]);
        User::factory()->create(['tenant_id' => $inactiveTenant->id]);
        User::factory()->superAdmin()->create();

        // Actividad en los últimos 30 días solo para el tenant activo
        LoginLog::factory()->count(2)->create([
            'user_id' => $user->id,
            'tenant_id' => $activeTenant->id,
            'created_at' => $now->copy()->subDays(5),
        ]);
        LoginLog::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $inactiveTenant->id,
            'created_at' => $now->copy()->subDays(40),
        ]);

        // Solo el pago aprobado dentro de los últimos 30 días cuenta como ingreso
        Payment::factory()->create([
            'tenant_id' => $activeTenant->id,
            'status' => Payment::STATUS_APPROVED,
            'amount' => 10000,
            'paid_at' => $now->copy()->subDays(3),
        ]);
        Payment::factory()->create([
            'tenant_id' => $activeTenant->id,
            'status' => Payment::STATUS_APPROVED,
            'amount' => 5000,
            'paid_at' => $now->copy()->subDays(40),
        ]);
        Payment::factory()->create([
            'tenant_id' => $activeTenant->id,
            'status' => Payment::STATUS_PENDING,
            'amount' => 7000,
            'paid_at' => $now->copy()->subDays(2),
        ]);

        $stats = $this->service->getStats($now, $thirtyDaysAgo, $sevenDaysAgo);

        $this->assertSame(3, $stats['total_tenants']);
        $this->assertSame(2, $stats['active_tenants']);
        $this->assertSame(2, $stats['total_users']);
        $this->assertSame(1, $stats['expired_subscriptions']);
        $this->assertSame(1, $stats['tenants_with_activity']);
        $this->assertEqualsWithDelta(33.3, $stats['retention_percent'], 0.01);
        $this->assertSame(10000, $stats['approved_revenue_30d']);
        $this->assertArrayHasKey('expiring_soon', $stats);
    }

    public function test_get_stats_returns_zero_retention_without_tenants(): void
    {
        $now = now();

        $stats = $this->service->getStats($now, $now->copy()->subDays(30), $now->copy()->subDays(7));

        $this->assertSame(0, $stats['total_tenants']);
        $this->assertSame(0, $stats['retention_percent']);
        $this->assertSame(0, $stats['approved_revenue_30d']);
    }

    public function test_get_work_orders_by_tenant_groups_and_orders_by_total(): void
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $tenantA = Tenant::factory()->create(['name' => 'Taller A']);
        $tenantB = Tenant::factory()->create(['name' => 'Taller B']);
        $tenantC = Tenant::factory()->create(['name' => 'Taller C']);

        WorkOrder::factory()->count(3)->create([
            'tenant_id' => $tenantA->id,
            'created_at' => $now->copy()->subDays(5),
        ]);
        WorkOrder::factory()->create([
            'tenant_id' => $tenantB->id,
            'created_at' => $now->copy()->subDays(10),
        ]);

        // Fuera de la ventana de 30 días: no debe aparecer
        WorkOrder::factory()->create([
            'tenant_id' => $tenantC->id,
            'created_at' => $now->copy()->subDays(40),
        ]);

        $result = $this->service->getWorkOrdersByTenant($thirtyDaysAgo);

        $this->assertCount(2, $result);
        $this->assertSame(['tenant' => 'Taller A', 'total' => 3], $result[0]);
        $this->assertSame(['tenant' => 'Taller B', 'total' => 1], $result[1]);
    }

    public function test_get_work_orders_by_tenant_limits_to_ten_tenants(): void
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $topTenant = Tenant::factory()->create(['name' => 'Taller Top']);
        WorkOrder::factory()->count(2)->create([
            'tenant_id' => $topTenant->id,
            'created_at' => $now->copy()->subDays(2),
        ]);

        Tenant::factory()->count(10)->create()->each(function (Tenant $tenant) use ($now): void {
            WorkOrder::factory()->create([
                'tenant_id' => $tenant->id,
                'created_at' => $now->copy()->subDays(2),
            ]);
        });

        $result = $this->service->getWorkOrdersByTenant($thirtyDaysAgo);

        $this->assertCount(10, $result);
        $this->assertSame(['tenant' => 'Taller Top', 'total' => 2], $result[0]);
    }
}
