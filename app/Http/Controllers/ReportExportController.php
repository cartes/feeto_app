<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlanFeatureService;
use App\Services\Reports\ReportDataService;
use App\Services\Reports\ReportWorkbookExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __construct(
        protected PlanFeatureService $planFeatureService,
        protected ReportDataService $reportDataService,
    ) {}

    public function pdf(string $report): HttpResponse
    {
        $tenant = $this->ensureCommercialReportsEnabled();
        $definition = $this->reportDataService->exportDefinition($report);

        return Pdf::loadView('reports.export', [
            'definition' => $definition,
            'tenantName' => $tenant->name,
            'generatedAt' => now(),
        ])
            ->setPaper('a4')
            ->download($definition['file_name'].'.pdf');
    }

    public function excel(string $report): BinaryFileResponse
    {
        $this->ensureCommercialReportsEnabled();
        $definition = $this->reportDataService->exportDefinition($report);

        return Excel::download(
            new ReportWorkbookExport($definition),
            $definition['file_name'].'.xlsx',
        );
    }

    private function ensureCommercialReportsEnabled(): Tenant
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        return $tenant;
    }
}
