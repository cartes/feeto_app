<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\LoginLog;
use App\Models\PageVisit;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

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

        // Visitas diarias últimos 30 días
        $visitsByDay = PageVisit::query()
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->select('date', DB::raw('sum(visits) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->toDateString(),
                'visits' => (int) $row->total,
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
            'expiring_tenants' => $expiringTenants,
        ]);
    }
}
