<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

        $overdueInvoices = ClientInvoice::query()
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0);

        return Inertia::render('Reports/Index', [
            'summary' => [
                'total_quotes' => Quote::query()->count(),
                'critical_products' => Product::query()->where('physical_stock', '<=', 0)->count(),
                'total_clients' => Client::query()->count(),
                'overdue_invoices' => $overdueInvoices->count(),
            ],
            'reports' => [
                [
                    'name' => 'Ventas',
                    'description' => 'Cotizaciones, aceptaciones y cartera vencida.',
                    'route' => 'reports.sales',
                    'category' => 'Comercial',
                ],
                [
                    'name' => 'Supervisión',
                    'description' => 'Descuentos, cotizaciones pendientes y foco de cobranza.',
                    'route' => 'reports.supervisors',
                    'category' => 'Control',
                ],
                [
                    'name' => 'Existencias',
                    'description' => 'Stock crítico, valorización y repuestos comprometidos.',
                    'route' => 'reports.inventory',
                    'category' => 'Operación',
                ],
                [
                    'name' => 'Clientes',
                    'description' => 'Cartera activa, clientes top y seguimiento comercial.',
                    'route' => 'reports.customers',
                    'category' => 'CRM',
                ],
                [
                    'name' => 'Cobranza',
                    'description' => 'Mora, tramos de deuda y clientes con mayor riesgo.',
                    'route' => 'reports.collections',
                    'category' => 'Caja',
                ],
            ],
        ]);
    }
}
