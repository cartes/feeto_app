<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlanFeatureService;
use App\Services\Reports\ReportDataService;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReportController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected ReportDataService $reportDataService,
    ) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        return Inertia::render('Reports/Inventory', $this->reportDataService->inventory());
    }
}
