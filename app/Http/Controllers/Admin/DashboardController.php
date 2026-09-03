<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Services\AdminDashboardService;
use App\Services\VisitAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboard,
        private readonly VisitAnalyticsService $visits,
    ) {}

    public function __invoke(Request $request): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

        $visitsPeriod = VisitAnalyticsService::normalizePeriod($request->query('period'));
        $visitsScope = VisitAnalyticsService::normalizeScope($request->query('scope'), PageVisit::SCOPE_SITE);

        $tenantScatter = $this->dashboard->getTenantScatter($thirtyDaysAgo);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->dashboard->getStats($now, $thirtyDaysAgo, $sevenDaysAgo),
            'work_orders_by_tenant' => $this->dashboard->getWorkOrdersByTenant($thirtyDaysAgo),
            'ocr_usage' => $this->dashboard->getOcrUsage($thirtyDaysAgo),
            'visits' => $this->visits->dashboardSnapshot($visitsPeriod, $visitsScope),
            'expiring_tenants' => $this->dashboard->getExpiringTenants($now),
            'pending_trial_requests' => $this->dashboard->getPendingTrialRequestsCount(),
            'recent_trial_requests' => $this->dashboard->getRecentTrialRequests(),
            'most_active_tenants' => $this->dashboard->getMostActiveTenants($thirtyDaysAgo),
            'new_tenants_by_month' => $this->dashboard->getNewTenantsByMonth($now),
            'tenant_scatter' => $tenantScatter['points'],
            'scatter_medians' => $tenantScatter['medians'],
        ]);
    }
}
