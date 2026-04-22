<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('reports.sales', ['tenantBySlug' => Tenant::current()?->slug]);
    }
}
