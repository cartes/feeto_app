<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\LoginLog;
use App\Models\PageVisit;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

        $period = $request->query('period', '30d');
        if (! in_array($period, ['7d', '30d', '90d'], true)) {
            $period = '30d';
        }

        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        };

        $visitsStartDate = $now->copy()->subDays($days);

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalUsers = User::where('is_super_admin', false)->count();
        $expiredSubscriptions = Tenant::where('subscription_ends_at', '<', $now)->whereNotNull('subscription_ends_at')->count();
        $expiringSoon = Tenant::whereBetween('subscription_ends_at', [$now, $sevenDaysAgo->addDays(7)])->count();

        // Tenants que tuvieron al menos 1 login en los últimos 30 días
        $tenantsWithActivity = LoginLog::where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('tenant_id')
            ->distinct('tenant_id')
            ->count('tenant_id');

        $retentionPercent = $totalTenants > 0 ? round($tenantsWithActivity / $totalTenants * 100, 1) : 0;

        // Work orders últimos 30 días por tenant
        $workOrdersByTenant = WorkOrder::query()
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

        // Llamadas OCR últimos 30 días por tenant
        $ocrUsage = ApiUsageLog::query()
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

        // Visitas diarias últimos N días
        $visitsByDay = PageVisit::query()
            ->where('date', '>=', $visitsStartDate->toDateString())
            ->select('date', DB::raw('sum(visits) as total'), DB::raw('sum(unique_visits) as unique_total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->toDateString(),
                'visits' => (int) $row->total,
                'unique_visits' => (int) $row->unique_total,
            ]);

        // Ingresos aprobados (MRR aproximado)
        $approvedRevenue = Payment::query()
            ->where('status', 'approved')
            ->where('paid_at', '>=', $thirtyDaysAgo)
            ->sum('amount');

        // Tenants próximos a vencer (7 días)
        $expiringTenants = Tenant::query()
            ->whereBetween('subscription_ends_at', [$now, $now->copy()->addDays(7)])
            ->select('id', 'name', 'subscription_ends_at')
            ->get();

        // Solicitudes de prueba gratuita
        $pendingTrialRequests = TrialRequest::where('status', 'pending')->count();
        $recentTrialRequests = TrialRequest::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'business_name', 'business_type', 'city', 'created_at']);

        // Tenants más activos (por logins en últimos 30 días)
        $mostActiveTenantsChart = LoginLog::query()
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

        // Nuevos tenants por mes (últimos 6 meses)
        $tenantMonthExpression = $this->monthGroupingExpression('created_at');

        $newTenantsByMonth = Tenant::query()
            ->where('created_at', '>=', $now->copy()->subMonths(6))
            ->select(DB::raw("{$tenantMonthExpression} as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'total' => (int) $r->total]);

        // --- Scatter: actividad vs. tamaño por tenant ---
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

        $workOrdersByTenantMap = WorkOrder::query()
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
            'work_orders' => (int) ($workOrdersByTenantMap[$t->id] ?? 0),
        ]);

        // Medianas para definir cuadrantes
        $medianUsers = $scatterPoints->median('users') ?? 1;
        $medianLogins = $scatterPoints->median('logins') ?? 1;

        $tenantScatter = $scatterPoints->map(fn ($p) => [
            ...$p,
            'quadrant' => match (true) {
                $p['users'] >= $medianUsers && $p['logins'] >= $medianLogins => 'champions',
                $p['users'] < $medianUsers && $p['logins'] >= $medianLogins => 'growing',
                $p['users'] >= $medianUsers && $p['logins'] < $medianLogins => 'at_risk',
                default => 'sleeping',
            },
        ])->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'total_users' => $totalUsers,
                'expired_subscriptions' => $expiredSubscriptions,
                'expiring_soon' => $expiringSoon,
                'retention_percent' => $retentionPercent,
                'tenants_with_activity' => $tenantsWithActivity,
                'approved_revenue_30d' => (int) $approvedRevenue,
            ],
            'work_orders_by_tenant' => $workOrdersByTenant,
            'ocr_usage' => $ocrUsage,
            'visits_by_day' => $visitsByDay,
            'current_period' => $period,
            'expiring_tenants' => $expiringTenants,
            'pending_trial_requests' => $pendingTrialRequests,
            'recent_trial_requests' => $recentTrialRequests,
            'most_active_tenants' => $mostActiveTenantsChart,
            'new_tenants_by_month' => $newTenantsByMonth,
            'tenant_scatter' => $tenantScatter,
            'scatter_medians' => ['users' => $medianUsers, 'logins' => $medianLogins],
        ]);
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
