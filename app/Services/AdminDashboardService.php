<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiUsageLog;
use App\Models\LoginLog;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Estadísticas generales del dashboard admin.
     *
     * @return array{
     *     total_tenants: int,
     *     active_tenants: int,
     *     total_users: int,
     *     expired_subscriptions: int,
     *     expiring_soon: int,
     *     retention_percent: float|int,
     *     tenants_with_activity: int,
     *     approved_revenue_30d: int,
     * }
     */
    public function getStats(Carbon $now, Carbon $thirtyDaysAgo, Carbon $sevenDaysAgo): array
    {
        $totalTenants = Tenant::count();

        // Tenants que tuvieron al menos 1 login en los últimos 30 días
        $tenantsWithActivity = LoginLog::where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('tenant_id')
            ->distinct('tenant_id')
            ->count('tenant_id');

        // Ingresos aprobados (MRR aproximado)
        $approvedRevenue = Payment::query()
            ->where('status', 'approved')
            ->where('paid_at', '>=', $thirtyDaysAgo)
            ->sum('amount');

        return [
            'total_tenants' => $totalTenants,
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'total_users' => User::where('is_super_admin', false)->count(),
            'expired_subscriptions' => Tenant::where('subscription_ends_at', '<', $now)->whereNotNull('subscription_ends_at')->count(),
            'expiring_soon' => Tenant::whereBetween('subscription_ends_at', [$now, $sevenDaysAgo->addDays(7)])->count(),
            'retention_percent' => $totalTenants > 0 ? round($tenantsWithActivity / $totalTenants * 100, 1) : 0,
            'tenants_with_activity' => $tenantsWithActivity,
            'approved_revenue_30d' => (int) $approvedRevenue,
        ];
    }

    /**
     * Work orders últimos 30 días por tenant (top 10).
     *
     * @return Collection<int, array{tenant: string, total: int}>
     */
    public function getWorkOrdersByTenant(Carbon $thirtyDaysAgo): Collection
    {
        return WorkOrder::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('tenant_id', DB::raw('count(*) as total'))
            ->groupBy('tenant_id')
            ->with('tenant:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'tenant' => $row->tenant?->name ?? 'Sin tenant',
                'total' => $row->total,
            ]);
    }

    /**
     * Llamadas OCR últimos 30 días por tenant (top 10).
     *
     * @return Collection<int, array{tenant: string, total: int}>
     */
    public function getOcrUsage(Carbon $thirtyDaysAgo): Collection
    {
        return ApiUsageLog::query()
            ->where('service', 'ocr')
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->select('tenant_id', DB::raw('sum(calls_count) as total'))
            ->groupBy('tenant_id')
            ->with('tenant:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'tenant' => $row->tenant?->name ?? 'Sin tenant',
                'total' => (int) $row->total,
            ]);
    }

    /**
     * Tenants próximos a vencer (7 días).
     *
     * @return Collection<int, Tenant>
     */
    public function getExpiringTenants(Carbon $now): Collection
    {
        return Tenant::query()
            ->whereBetween('subscription_ends_at', [$now, $now->copy()->addDays(7)])
            ->select('id', 'name', 'subscription_ends_at')
            ->get();
    }

    public function getPendingTrialRequestsCount(): int
    {
        return TrialRequest::where('status', 'pending')->count();
    }

    /**
     * Últimas 5 solicitudes de prueba gratuita pendientes.
     *
     * @return Collection<int, TrialRequest>
     */
    public function getRecentTrialRequests(): Collection
    {
        return TrialRequest::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'business_name', 'business_type', 'city', 'created_at']);
    }

    /**
     * Tenants más activos por logins en últimos 30 días (top 8).
     *
     * @return Collection<int, array{name: string, logins: int}>
     */
    public function getMostActiveTenants(Carbon $thirtyDaysAgo): Collection
    {
        return LoginLog::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('tenant_id')
            ->select('tenant_id', DB::raw('count(*) as logins'))
            ->groupBy('tenant_id')
            ->orderByDesc('logins')
            ->limit(8)
            ->with('tenant:id,name')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->tenant?->name ?? 'Sin nombre',
                'logins' => (int) $r->logins,
            ]);
    }

    /**
     * Nuevos tenants por mes (últimos 6 meses).
     *
     * @return Collection<int, array{month: string, total: int}>
     */
    public function getNewTenantsByMonth(Carbon $now): Collection
    {
        $tenantMonthExpression = $this->monthGroupingExpression('created_at');

        return Tenant::query()
            ->where('created_at', '>=', $now->copy()->subMonths(6))
            ->select(DB::raw("{$tenantMonthExpression} as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'total' => (int) $r->total]);
    }

    /**
     * Scatter: actividad (logins) vs. tamaño (usuarios) por tenant activo,
     * con cuadrante asignado según las medianas.
     *
     * @return array{
     *     points: Collection<int, array{id: int, name: string, plan: string, users: int, logins: int, work_orders: int, quadrant: string}>,
     *     medians: array{users: float|int, logins: float|int},
     * }
     */
    public function getTenantScatter(Carbon $thirtyDaysAgo): array
    {
        $allTenants = Tenant::query()
            ->where('status', 'active')
            ->withCount('users')
            ->get(['id', 'name', 'plan', 'status']);

        $loginsByTenant = LoginLog::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('tenant_id')
            ->select('tenant_id', DB::raw('count(*) as total'))
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $workOrdersByTenant = WorkOrder::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('tenant_id', DB::raw('count(*) as total'))
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $scatterPoints = $allTenants->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'plan' => $t->plan ?? 'gratuito',
            'users' => (int) $t->users_count,
            'logins' => (int) ($loginsByTenant[$t->id] ?? 0),
            'work_orders' => (int) ($workOrdersByTenant[$t->id] ?? 0),
        ]);

        // Medianas para definir cuadrantes
        $medianUsers = $scatterPoints->median('users') ?? 1;
        $medianLogins = $scatterPoints->median('logins') ?? 1;

        $points = $scatterPoints->map(fn ($p) => [
            ...$p,
            'quadrant' => match (true) {
                $p['users'] >= $medianUsers && $p['logins'] >= $medianLogins => 'champions',
                $p['users'] < $medianUsers && $p['logins'] >= $medianLogins => 'growing',
                $p['users'] >= $medianUsers && $p['logins'] < $medianLogins => 'at_risk',
                default => 'sleeping',
            },
        ])->values();

        return [
            'points' => $points,
            'medians' => ['users' => $medianUsers, 'logins' => $medianLogins],
        ];
    }

    private function monthGroupingExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
