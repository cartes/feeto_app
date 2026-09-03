<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VisitAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitAnalyticsController extends Controller
{
    public function __construct(
        private readonly VisitAnalyticsService $analytics,
    ) {}

    public function __invoke(Request $request): Response
    {
        $period = VisitAnalyticsService::normalizePeriod($request->query('period'));
        $scope = VisitAnalyticsService::normalizeScope($request->query('scope'));

        return Inertia::render('Admin/Analytics/Visits', [
            'report' => $this->analytics->report($period, $scope),
        ]);
    }
}
