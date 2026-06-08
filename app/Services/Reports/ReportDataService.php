<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Tenant;
use App\Models\TenantLead;
use App\Models\WorkOrder;
use App\Services\ClientCrmService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReportDataService
{
    /**
     * @var array<int, string>
     */
    private const REPORT_KEYS = [
        'sales',
        'supervisors',
        'inventory',
        'customers',
        'collections',
        'acquisition',
    ];

    public function __construct(protected ClientCrmService $clientCrmService) {}

    /**
     * @return array<int, string>
     */
    public static function reportKeys(): array
    {
        return self::REPORT_KEYS;
    }

    /**
     * @return array<string, mixed>
     */
    public function sales(): array
    {
        $quotes = Quote::query()
            ->with('workOrder.vehicle.client')
            ->latest()
            ->get();

        $acceptedQuotes = $quotes->where('status', Quote::STATUS_ACCEPTED);
        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        return [
            'summary' => [
                'total_quotes' => $quotes->count(),
                'accepted_quotes' => $acceptedQuotes->count(),
                'quoted_amount' => (float) $quotes->sum('subtotal_amount'),
                'accepted_amount' => (float) $acceptedQuotes->sum('subtotal_amount'),
                'overdue_invoices' => $overdueInvoices->count(),
                'overdue_amount' => (float) $overdueInvoices->sum('amount_due'),
            ],
            'recentQuotes' => $quotes->take(10)->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'status' => $quote->status,
                'subtotal_amount' => (float) $quote->subtotal_amount,
                'work_order_id' => $quote->work_order_id,
                'client_name' => $quote->workOrder?->vehicle?->client?->name,
                'plate' => $quote->workOrder?->vehicle?->plate,
                'sent_at' => $quote->sent_at?->toISOString(),
                'responded_at' => $quote->responded_at?->toISOString(),
            ])->values()->all(),
            'overdueInvoices' => $overdueInvoices->take(8)->map(fn (ClientInvoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name ?? 'Cliente',
                'amount_due' => (float) $invoice->amount_due,
                'due_at' => $invoice->due_at?->toDateString(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisors(): array
    {
        $tenant = Tenant::current();
        $threshold = $tenant?->maxDiscountWithoutApproval() ?? 0;

        $discountedItems = QuoteItem::query()
            ->with('quote.workOrder.vehicle.client')
            ->where('discount_percent', '>', 0)
            ->latest()
            ->get();

        $highDiscountItems = $discountedItems
            ->where('discount_percent', '>', $threshold)
            ->values();

        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        return [
            'summary' => [
                'discounted_items' => $discountedItems->count(),
                'high_discount_items' => $highDiscountItems->count(),
                'high_discount_amount' => (float) $highDiscountItems->sum('discount_amount'),
                'pending_quotes' => Quote::query()->where('status', Quote::STATUS_PENDING_CUSTOMER)->count(),
                'overdue_invoices' => $overdueInvoices->count(),
            ],
            'discountThreshold' => $threshold,
            'recentDiscounts' => $discountedItems->take(10)->map(fn (QuoteItem $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'work_order_id' => $item->quote?->work_order_id,
                'client_name' => $item->quote?->workOrder?->vehicle?->client?->name,
            ])->values()->all(),
            'overdueInvoices' => $overdueInvoices->take(8)->map(fn (ClientInvoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name ?? 'Cliente',
                'amount_due' => (float) $invoice->amount_due,
                'due_at' => $invoice->due_at?->toDateString(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inventory(): array
    {
        $products = Product::query()
            ->latest()
            ->get();

        $criticalProducts = $products
            ->where('physical_stock', '<=', 0)
            ->sortBy('name')
            ->values();

        $lowStockProducts = $products
            ->filter(fn (Product $product): bool => $product->physical_stock > 0 && $product->physical_stock <= $product->min_stock)
            ->sortBy('physical_stock')
            ->values();

        $reservedProducts = $products
            ->where('reserved_stock', '>', 0)
            ->sortByDesc('reserved_stock')
            ->take(8)
            ->values();

        $highValueProducts = $products
            ->sortByDesc(fn (Product $product): float => (float) $product->cost_price * $product->physical_stock)
            ->take(8)
            ->values();

        return [
            'summary' => [
                'total_products' => $products->count(),
                'physical_units' => (int) $products->sum('physical_stock'),
                'reserved_units' => (int) $products->sum('reserved_stock'),
                'available_units' => (int) $products->sum(fn (Product $product): int => max($product->physical_stock - $product->reserved_stock, 0)),
                'critical_products' => $criticalProducts->count(),
                'low_stock_products' => $lowStockProducts->count(),
                'inventory_cost_value' => (float) $products->sum(fn (Product $product): float => (float) $product->cost_price * $product->physical_stock),
                'inventory_sales_value' => (float) $products->sum(fn (Product $product): float => (float) $product->selling_price * $product->physical_stock),
            ],
            'criticalProducts' => $criticalProducts
                ->take(8)
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values()
                ->all(),
            'lowStockProducts' => $lowStockProducts
                ->take(8)
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values()
                ->all(),
            'reservedProducts' => $reservedProducts
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values()
                ->all(),
            'highValueProducts' => $highValueProducts
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customers(): array
    {
        $ninetyDaysAgo = now()->subDays(90);

        $clients = $this->clientMetricsQuery()
            ->get()
            ->map(fn (Client $client): array => $this->clientCrmService->buildIndexItem($client));

        $activeClients = $clients->filter(
            fn (array $client): bool => $this->isRecentVisit($client['metrics']['last_visit'] ?? null, $ninetyDaysAgo)
        );

        $inactiveClients = $clients->filter(
            fn (array $client): bool => ! $this->isRecentVisit($client['metrics']['last_visit'] ?? null, $ninetyDaysAgo)
        );

        $completedWorkOrders = WorkOrder::query()
            ->where('status', WorkOrder::STATUS_LISTO);

        $overdueInvoices = ClientInvoice::query()
            ->with('client')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->get();

        $overdueClients = $overdueInvoices
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                /** @var ClientInvoice $invoice */
                $invoice = $group->first();

                return [
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client?->name ?? 'Cliente',
                    'invoice_count' => $group->count(),
                    'amount_due' => (float) $group->sum('amount_due'),
                ];
            })
            ->sortByDesc('amount_due')
            ->take(8)
            ->values();

        return [
            'summary' => [
                'total_clients' => $clients->count(),
                'active_clients' => $activeClients->count(),
                'inactive_clients' => $inactiveClients->count(),
                'clients_with_overdue_invoices' => $overdueInvoices->pluck('client_id')->filter()->unique()->count(),
                'lifetime_value' => (float) $completedWorkOrders->sum('total_amount'),
                'average_ticket' => (float) ($completedWorkOrders->avg('total_amount') ?? 0),
            ],
            'topClients' => $clients
                ->sortByDesc(fn (array $client): float => (float) ($client['metrics']['total_spent'] ?? 0))
                ->take(8)
                ->values()
                ->all(),
            'inactiveClients' => $inactiveClients
                ->sortBy(fn (array $client): string => $client['metrics']['last_visit'] ?? '')
                ->take(8)
                ->values()
                ->all(),
            'overdueClients' => $overdueClients->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collections(): array
    {
        $openStatuses = [
            ClientInvoice::STATUS_PENDING,
            ClientInvoice::STATUS_PARTIAL,
            ClientInvoice::STATUS_OVERDUE,
        ];

        $openInvoices = ClientInvoice::query()
            ->with('client')
            ->whereIn('status', $openStatuses)
            ->where('amount_due', '>', 0)
            ->get();

        $overdueInvoices = $openInvoices
            ->filter(fn (ClientInvoice $invoice): bool => $invoice->isOverdue())
            ->values();

        $agingBuckets = collect([
            ['label' => '1 a 30 dias', 'range' => [1, 30]],
            ['label' => '31 a 60 dias', 'range' => [31, 60]],
            ['label' => '61+ dias', 'range' => [61, null]],
        ])->map(function (array $bucket) use ($overdueInvoices): array {
            [$min, $max] = $bucket['range'];

            $items = $overdueInvoices->filter(function (ClientInvoice $invoice) use ($min, $max): bool {
                $daysOverdue = (int) $invoice->due_at->diffInDays(now());

                if ($max === null) {
                    return $daysOverdue >= $min;
                }

                return $daysOverdue >= $min && $daysOverdue <= $max;
            });

            return [
                'label' => $bucket['label'],
                'count' => $items->count(),
                'amount' => (float) $items->sum('amount_due'),
            ];
        })->values();

        $topDebtors = $overdueInvoices
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                /** @var ClientInvoice $invoice */
                $invoice = $group->first();

                return [
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client?->name ?? 'Cliente',
                    'invoice_count' => $group->count(),
                    'amount_due' => (float) $group->sum('amount_due'),
                ];
            })
            ->sortByDesc('amount_due')
            ->take(8)
            ->values();

        $criticalInvoices = $overdueInvoices
            ->sortByDesc(fn (ClientInvoice $invoice): int => (int) $invoice->due_at->diffInDays(now()))
            ->take(10)
            ->map(fn (ClientInvoice $invoice): array => $this->serializeInvoice($invoice))
            ->values();

        $followUpInvoices = $openInvoices
            ->filter(fn (ClientInvoice $invoice): bool => $invoice->whatsapp_reminder_count > 0)
            ->sortByDesc('whatsapp_reminder_count')
            ->take(8)
            ->map(fn (ClientInvoice $invoice): array => $this->serializeInvoice($invoice))
            ->values();

        return [
            'summary' => [
                'total_invoices' => ClientInvoice::query()->count(),
                'open_invoices' => $openInvoices->count(),
                'overdue_invoices' => $overdueInvoices->count(),
                'amount_due' => (float) $openInvoices->sum('amount_due'),
                'overdue_amount' => (float) $overdueInvoices->sum('amount_due'),
                'average_days_overdue' => round((float) $overdueInvoices->avg(fn (ClientInvoice $invoice): int => (int) $invoice->due_at->diffInDays(now())), 1),
            ],
            'agingBuckets' => $agingBuckets->all(),
            'topDebtors' => $topDebtors->all(),
            'criticalInvoices' => $criticalInvoices->all(),
            'followUpInvoices' => $followUpInvoices->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function acquisition(array $filters = []): array
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            throw new InvalidArgumentException('A current tenant is required to build the acquisition report.');
        }

        $range = $this->resolveAcquisitionRange(isset($filters['range']) && is_string($filters['range']) ? $filters['range'] : null);
        $landingPath = 'taller/'.$tenant->slug;

        $pageVisits = PageVisit::query()
            ->where('tenant_id', $tenant->id)
            ->where('path', $landingPath)
            ->whereDate('date', '>=', $range['start']->toDateString())
            ->whereDate('date', '<=', $range['end']->toDateString())
            ->orderBy('date')
            ->get();

        $leads = TenantLead::query()
            ->whereBetween('occurred_at', [$range['start'], $range['end']])
            ->orderByDesc('occurred_at')
            ->get();

        $appointments = Appointment::query()
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->orderByDesc('created_at')
            ->get();

        $summary = $this->buildAcquisitionSummary($pageVisits, $leads, $appointments);

        return [
            'filters' => [
                'range' => $range['key'],
                'label' => $range['label'],
                'start' => $range['start']->toDateString(),
                'end' => $range['end']->toDateString(),
            ],
            'rangeOptions' => $this->acquisitionRangeOptions(),
            'summary' => $summary,
            'funnel' => [
                [
                    'label' => 'Visitas unicas',
                    'count' => $summary['unique_visits'],
                    'conversion_rate' => 100.0,
                    'description' => 'Personas unicas que llegaron al landing del taller.',
                ],
                [
                    'label' => 'Intentos de contacto',
                    'count' => $summary['engaged_leads'],
                    'conversion_rate' => $summary['visit_to_engaged_rate'],
                    'description' => 'WhatsApp y formularios capturados desde la landing.',
                ],
                [
                    'label' => 'Citas agendadas',
                    'count' => $summary['booked_appointments'],
                    'conversion_rate' => $summary['visit_to_booking_rate'],
                    'description' => 'Reservas creadas durante el periodo seleccionado.',
                ],
            ],
            'dailySeries' => $this->buildAcquisitionDailySeries($pageVisits, $leads, $appointments, $range),
            'recentActivity' => $this->buildRecentAcquisitionActivity($leads, $appointments),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportDefinition(string $report): array
    {
        return match ($report) {
            'sales' => $this->salesExportDefinition(),
            'supervisors' => $this->supervisorsExportDefinition(),
            'inventory' => $this->inventoryExportDefinition(),
            'customers' => $this->customersExportDefinition(),
            'collections' => $this->collectionsExportDefinition(),
            'acquisition' => $this->acquisitionExportDefinition(),
            default => throw new InvalidArgumentException("Unknown report [{$report}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function salesExportDefinition(): array
    {
        $data = $this->sales();

        return [
            'report' => 'sales',
            'title' => 'Reporte de Ventas',
            'description' => 'Cotizaciones, aceptaciones y cartera vencida.',
            'file_name' => $this->buildFileName('reporte-ventas'),
            'summary' => [
                ['label' => 'Cotizaciones Totales', 'value' => $data['summary']['total_quotes'], 'type' => 'number'],
                ['label' => 'Cotizaciones Aceptadas', 'value' => $data['summary']['accepted_quotes'], 'type' => 'number'],
                ['label' => 'Monto Cotizado', 'value' => $data['summary']['quoted_amount'], 'type' => 'currency'],
                ['label' => 'Monto Aceptado', 'value' => $data['summary']['accepted_amount'], 'type' => 'currency'],
                ['label' => 'Facturas Atrasadas', 'value' => $data['summary']['overdue_invoices'], 'type' => 'number'],
                ['label' => 'Monto en Mora', 'value' => $data['summary']['overdue_amount'], 'type' => 'currency'],
            ],
            'sections' => [
                [
                    'title' => 'Cotizaciones Recientes',
                    'columns' => [
                        ['key' => 'work_order_id', 'label' => 'OT', 'type' => 'number'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'plate', 'label' => 'Patente', 'type' => 'text'],
                        ['key' => 'status_label', 'label' => 'Estado', 'type' => 'text'],
                        ['key' => 'subtotal_amount', 'label' => 'Monto', 'type' => 'currency'],
                        ['key' => 'contacted_at', 'label' => 'Fecha', 'type' => 'date'],
                    ],
                    'rows' => collect($data['recentQuotes'])->map(fn (array $quote): array => [
                        'work_order_id' => $quote['work_order_id'],
                        'client_name' => $quote['client_name'] ?? 'Sin cliente',
                        'plate' => $quote['plate'] ?? 'N/A',
                        'status_label' => $this->quoteStatusLabel((string) $quote['status']),
                        'subtotal_amount' => $quote['subtotal_amount'],
                        'contacted_at' => $quote['responded_at'] ?? $quote['sent_at'],
                    ])->all(),
                ],
                [
                    'title' => 'Facturas Atrasadas',
                    'columns' => [
                        ['key' => 'invoice_number', 'label' => 'Factura', 'type' => 'text'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                        ['key' => 'due_at', 'label' => 'Vencimiento', 'type' => 'date'],
                    ],
                    'rows' => $data['overdueInvoices'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supervisorsExportDefinition(): array
    {
        $data = $this->supervisors();

        return [
            'report' => 'supervisors',
            'title' => 'Reporte de Supervisores',
            'description' => 'Descuentos aplicados, cotizaciones pendientes y foco de cobranza.',
            'file_name' => $this->buildFileName('reporte-supervisores'),
            'summary' => [
                ['label' => 'Umbral Configurado', 'value' => $data['discountThreshold'], 'type' => 'percent'],
                ['label' => 'Items con Descuento', 'value' => $data['summary']['discounted_items'], 'type' => 'number'],
                ['label' => 'Sobre Umbral', 'value' => $data['summary']['high_discount_items'], 'type' => 'number'],
                ['label' => 'Impacto Descuentos', 'value' => $data['summary']['high_discount_amount'], 'type' => 'currency'],
                ['label' => 'Cotizaciones Pendientes', 'value' => $data['summary']['pending_quotes'], 'type' => 'number'],
                ['label' => 'Facturas Criticas', 'value' => $data['summary']['overdue_invoices'], 'type' => 'number'],
            ],
            'sections' => [
                [
                    'title' => 'Descuentos Recientes',
                    'columns' => [
                        ['key' => 'description', 'label' => 'Item', 'type' => 'text'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'discount_percent', 'label' => '% Desc.', 'type' => 'percent'],
                        ['key' => 'discount_amount', 'label' => 'Impacto', 'type' => 'currency'],
                        ['key' => 'work_order_id', 'label' => 'OT', 'type' => 'number'],
                    ],
                    'rows' => collect($data['recentDiscounts'])->map(fn (array $item): array => [
                        'description' => $item['description'],
                        'client_name' => $item['client_name'] ?? 'Sin cliente',
                        'discount_percent' => $item['discount_percent'],
                        'discount_amount' => $item['discount_amount'],
                        'work_order_id' => $item['work_order_id'],
                    ])->all(),
                ],
                [
                    'title' => 'Facturas Criticas',
                    'columns' => [
                        ['key' => 'invoice_number', 'label' => 'Factura', 'type' => 'text'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                        ['key' => 'due_at', 'label' => 'Vencimiento', 'type' => 'date'],
                    ],
                    'rows' => $data['overdueInvoices'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryExportDefinition(): array
    {
        $data = $this->inventory();

        return [
            'report' => 'inventory',
            'title' => 'Reporte de Existencias',
            'description' => 'Stock critico, valorizacion del inventario y repuestos comprometidos.',
            'file_name' => $this->buildFileName('reporte-existencias'),
            'summary' => [
                ['label' => 'Productos', 'value' => $data['summary']['total_products'], 'type' => 'number'],
                ['label' => 'Unidades Fisicas', 'value' => $data['summary']['physical_units'], 'type' => 'number'],
                ['label' => 'Reservadas', 'value' => $data['summary']['reserved_units'], 'type' => 'number'],
                ['label' => 'Disponibles', 'value' => $data['summary']['available_units'], 'type' => 'number'],
                ['label' => 'Stock Critico', 'value' => $data['summary']['critical_products'], 'type' => 'number'],
                ['label' => 'Bajo Minimo', 'value' => $data['summary']['low_stock_products'], 'type' => 'number'],
                ['label' => 'Valor a Costo', 'value' => $data['summary']['inventory_cost_value'], 'type' => 'currency'],
                ['label' => 'Valor a Venta', 'value' => $data['summary']['inventory_sales_value'], 'type' => 'currency'],
            ],
            'sections' => [
                [
                    'title' => 'Stock Critico',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Producto', 'type' => 'text'],
                        ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                        ['key' => 'physical_stock', 'label' => 'Stock', 'type' => 'number'],
                        ['key' => 'min_stock', 'label' => 'Minimo', 'type' => 'number'],
                    ],
                    'rows' => $data['criticalProducts'],
                ],
                [
                    'title' => 'Bajo Minimo',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Producto', 'type' => 'text'],
                        ['key' => 'physical_stock', 'label' => 'Stock', 'type' => 'number'],
                        ['key' => 'available_stock', 'label' => 'Disponible', 'type' => 'number'],
                        ['key' => 'min_stock', 'label' => 'Minimo', 'type' => 'number'],
                    ],
                    'rows' => $data['lowStockProducts'],
                ],
                [
                    'title' => 'Mayor Valor Inmovilizado',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Producto', 'type' => 'text'],
                        ['key' => 'physical_stock', 'label' => 'Stock', 'type' => 'number'],
                        ['key' => 'cost_price', 'label' => 'Costo', 'type' => 'currency'],
                        ['key' => 'stock_cost_value', 'label' => 'Valor', 'type' => 'currency'],
                    ],
                    'rows' => $data['highValueProducts'],
                ],
                [
                    'title' => 'Stock Reservado',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Producto', 'type' => 'text'],
                        ['key' => 'reserved_stock', 'label' => 'Reservadas', 'type' => 'number'],
                        ['key' => 'available_stock', 'label' => 'Disponible', 'type' => 'number'],
                    ],
                    'rows' => $data['reservedProducts'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customersExportDefinition(): array
    {
        $data = $this->customers();

        return [
            'report' => 'customers',
            'title' => 'Reporte de Clientes',
            'description' => 'Actividad, valor generado y foco comercial de la cartera del taller.',
            'file_name' => $this->buildFileName('reporte-clientes'),
            'summary' => [
                ['label' => 'Clientes Totales', 'value' => $data['summary']['total_clients'], 'type' => 'number'],
                ['label' => 'Activos 90 Dias', 'value' => $data['summary']['active_clients'], 'type' => 'number'],
                ['label' => 'Inactivos', 'value' => $data['summary']['inactive_clients'], 'type' => 'number'],
                ['label' => 'Con Mora', 'value' => $data['summary']['clients_with_overdue_invoices'], 'type' => 'number'],
                ['label' => 'Valor Historico', 'value' => $data['summary']['lifetime_value'], 'type' => 'currency'],
                ['label' => 'Ticket Promedio', 'value' => $data['summary']['average_ticket'], 'type' => 'currency'],
            ],
            'sections' => [
                [
                    'title' => 'Clientes con Mayor Valor',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'rut', 'label' => 'RUT', 'type' => 'text'],
                        ['key' => 'visits_count', 'label' => 'Visitas', 'type' => 'number'],
                        ['key' => 'vehicles_count', 'label' => 'Vehiculos', 'type' => 'number'],
                        ['key' => 'total_spent', 'label' => 'Gasto', 'type' => 'currency'],
                        ['key' => 'last_visit', 'label' => 'Ultima Visita', 'type' => 'date'],
                    ],
                    'rows' => collect($data['topClients'])->map(fn (array $client): array => [
                        'name' => $client['name'],
                        'rut' => $client['rut'],
                        'visits_count' => $client['metrics']['visits_count'],
                        'vehicles_count' => $client['metrics']['vehicles_count'],
                        'total_spent' => $client['metrics']['total_spent'],
                        'last_visit' => $client['metrics']['last_visit'],
                    ])->all(),
                ],
                [
                    'title' => 'Clientes con Mora',
                    'columns' => [
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'invoice_count', 'label' => 'Facturas', 'type' => 'number'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                    ],
                    'rows' => $data['overdueClients'],
                ],
                [
                    'title' => 'Clientes para Reactivar',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'visits_count', 'label' => 'Visitas', 'type' => 'number'],
                        ['key' => 'vehicles_count', 'label' => 'Vehiculos', 'type' => 'number'],
                        ['key' => 'last_visit', 'label' => 'Ultima Visita', 'type' => 'date'],
                    ],
                    'rows' => collect($data['inactiveClients'])->map(fn (array $client): array => [
                        'name' => $client['name'],
                        'visits_count' => $client['metrics']['visits_count'],
                        'vehicles_count' => $client['metrics']['vehicles_count'],
                        'last_visit' => $client['metrics']['last_visit'],
                    ])->all(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionsExportDefinition(): array
    {
        $data = $this->collections();

        return [
            'report' => 'collections',
            'title' => 'Reporte de Cobranza',
            'description' => 'Mora, clientes de mayor riesgo y seguimiento de cobranza.',
            'file_name' => $this->buildFileName('reporte-cobranza'),
            'summary' => [
                ['label' => 'Facturas Totales', 'value' => $data['summary']['total_invoices'], 'type' => 'number'],
                ['label' => 'Abiertas', 'value' => $data['summary']['open_invoices'], 'type' => 'number'],
                ['label' => 'Vencidas', 'value' => $data['summary']['overdue_invoices'], 'type' => 'number'],
                ['label' => 'Saldo por Cobrar', 'value' => $data['summary']['amount_due'], 'type' => 'currency'],
                ['label' => 'Monto en Mora', 'value' => $data['summary']['overdue_amount'], 'type' => 'currency'],
                ['label' => 'Promedio Dias Mora', 'value' => $data['summary']['average_days_overdue'], 'type' => 'number'],
            ],
            'sections' => [
                [
                    'title' => 'Tramos de Mora',
                    'columns' => [
                        ['key' => 'label', 'label' => 'Tramo', 'type' => 'text'],
                        ['key' => 'count', 'label' => 'Facturas', 'type' => 'number'],
                        ['key' => 'amount', 'label' => 'Monto', 'type' => 'currency'],
                    ],
                    'rows' => $data['agingBuckets'],
                ],
                [
                    'title' => 'Facturas Criticas',
                    'columns' => [
                        ['key' => 'invoice_number', 'label' => 'Factura', 'type' => 'text'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'days_overdue', 'label' => 'Dias Mora', 'type' => 'number'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                        ['key' => 'whatsapp_reminder_count', 'label' => 'Recordatorios', 'type' => 'number'],
                    ],
                    'rows' => $data['criticalInvoices'],
                ],
                [
                    'title' => 'Principales Deudores',
                    'columns' => [
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'invoice_count', 'label' => 'Facturas', 'type' => 'number'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                    ],
                    'rows' => $data['topDebtors'],
                ],
                [
                    'title' => 'Seguimiento con Recordatorios',
                    'columns' => [
                        ['key' => 'invoice_number', 'label' => 'Factura', 'type' => 'text'],
                        ['key' => 'client_name', 'label' => 'Cliente', 'type' => 'text'],
                        ['key' => 'due_at', 'label' => 'Vencimiento', 'type' => 'date'],
                        ['key' => 'amount_due', 'label' => 'Saldo', 'type' => 'currency'],
                        ['key' => 'whatsapp_reminder_count', 'label' => 'Recordatorios', 'type' => 'number'],
                    ],
                    'rows' => $data['followUpInvoices'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function acquisitionExportDefinition(): array
    {
        $data = $this->acquisition();

        return [
            'report' => 'acquisition',
            'title' => 'Reporte de Adquisicion',
            'description' => 'Visitas, contactos y citas capturadas desde la landing publica del taller.',
            'file_name' => $this->buildFileName('reporte-adquisicion'),
            'summary' => [
                ['label' => 'Visitas Totales', 'value' => $data['summary']['total_visits'], 'type' => 'number'],
                ['label' => 'Visitas Unicas', 'value' => $data['summary']['unique_visits'], 'type' => 'number'],
                ['label' => 'WhatsApp', 'value' => $data['summary']['whatsapp_leads'], 'type' => 'number'],
                ['label' => 'Formularios', 'value' => $data['summary']['form_leads'], 'type' => 'number'],
                ['label' => 'Citas Agendadas', 'value' => $data['summary']['booked_appointments'], 'type' => 'number'],
                ['label' => 'Conv. Visita a Contacto', 'value' => $data['summary']['visit_to_engaged_rate'], 'type' => 'percent'],
                ['label' => 'Conv. Visita a Cita', 'value' => $data['summary']['visit_to_booking_rate'], 'type' => 'percent'],
            ],
            'sections' => [
                [
                    'title' => 'Serie Diaria',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Fecha', 'type' => 'date'],
                        ['key' => 'unique_visits', 'label' => 'Visitas Unicas', 'type' => 'number'],
                        ['key' => 'whatsapp_leads', 'label' => 'WhatsApp', 'type' => 'number'],
                        ['key' => 'form_leads', 'label' => 'Formularios', 'type' => 'number'],
                        ['key' => 'booked_appointments', 'label' => 'Citas', 'type' => 'number'],
                    ],
                    'rows' => $data['dailySeries'],
                ],
                [
                    'title' => 'Actividad Reciente',
                    'columns' => [
                        ['key' => 'source_label', 'label' => 'Canal', 'type' => 'text'],
                        ['key' => 'visitor_name', 'label' => 'Nombre', 'type' => 'text'],
                        ['key' => 'contact', 'label' => 'Contacto', 'type' => 'text'],
                        ['key' => 'detail', 'label' => 'Detalle', 'type' => 'text'],
                        ['key' => 'occurred_at', 'label' => 'Fecha', 'type' => 'date'],
                    ],
                    'rows' => $data['recentActivity'],
                ],
            ],
        ];
    }

    private function clientMetricsQuery(): Builder
    {
        return Client::query()
            ->select('clients.*')
            ->withCount(['vehicles', 'appointments', 'internalNotes'])
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->selectRaw('count(*)'),
                'work_orders_count',
            )
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->where('work_orders.status', WorkOrder::STATUS_LISTO)
                    ->selectRaw('coalesce(sum(work_orders.total_amount), 0)'),
                'total_spent',
            )
            ->selectSub(
                WorkOrder::query()
                    ->withoutGlobalScope('tenant')
                    ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
                    ->whereColumn('vehicles.client_id', 'clients.id')
                    ->whereColumn('work_orders.tenant_id', 'clients.tenant_id')
                    ->selectRaw('max(work_orders.created_at)'),
                'latest_work_order_at',
            )
            ->withMax('appointments as latest_appointment_at', 'appointment_date');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'physical_stock' => (int) $product->physical_stock,
            'reserved_stock' => (int) $product->reserved_stock,
            'available_stock' => max($product->physical_stock - $product->reserved_stock, 0),
            'min_stock' => (int) $product->min_stock,
            'cost_price' => (float) $product->cost_price,
            'selling_price' => (float) $product->selling_price,
            'stock_cost_value' => (float) $product->cost_price * $product->physical_stock,
            'stock_sales_value' => (float) $product->selling_price * $product->physical_stock,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoice(ClientInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name' => $invoice->client?->name ?? 'Cliente',
            'amount_due' => (float) $invoice->amount_due,
            'due_at' => $invoice->due_at?->toDateString(),
            'days_overdue' => $invoice->due_at ? (int) $invoice->due_at->diffInDays(now()) : 0,
            'whatsapp_reminder_count' => (int) $invoice->whatsapp_reminder_count,
        ];
    }

    private function isRecentVisit(?string $value, CarbonInterface $threshold): bool
    {
        if (! filled($value)) {
            return false;
        }

        return Carbon::parse($value)->gte($threshold);
    }

    private function buildFileName(string $base): string
    {
        $tenant = Tenant::current();
        $tenantSegment = $tenant?->slug ? Str::slug($tenant->slug) : 'tenant';

        return $tenantSegment.'-'.$base.'-'.now()->format('Ymd-His');
    }

    private function quoteStatusLabel(string $status): string
    {
        return match ($status) {
            Quote::STATUS_DRAFT => 'Borrador',
            Quote::STATUS_PENDING_CUSTOMER => 'Pendiente cliente',
            Quote::STATUS_ACCEPTED => 'Aceptada',
            Quote::STATUS_REJECTED => 'Rechazada',
            default => $status,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function acquisitionRangeOptions(): array
    {
        return [
            ['value' => '7d', 'label' => 'Ultimos 7 dias'],
            ['value' => '30d', 'label' => 'Ultimos 30 dias'],
            ['value' => '90d', 'label' => 'Ultimos 90 dias'],
            ['value' => '365d', 'label' => 'Ultimos 12 meses'],
        ];
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon}
     */
    private function resolveAcquisitionRange(?string $range): array
    {
        $definitions = collect($this->acquisitionRangeOptions())
            ->keyBy('value')
            ->map(fn (array $option): array => match ($option['value']) {
                '7d' => ['label' => $option['label'], 'days' => 7],
                '90d' => ['label' => $option['label'], 'days' => 90],
                '365d' => ['label' => $option['label'], 'days' => 365],
                default => ['label' => $option['label'], 'days' => 30],
            });

        $selectedKey = is_string($range) && $definitions->has($range) ? $range : '30d';
        $selected = $definitions->get($selectedKey);
        $end = now()->endOfDay();
        $start = now()->subDays(($selected['days'] ?? 30) - 1)->startOfDay();

        return [
            'key' => $selectedKey,
            'label' => $selected['label'] ?? 'Ultimos 30 dias',
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param  Collection<int, PageVisit>  $pageVisits
     * @param  Collection<int, TenantLead>  $leads
     * @param  Collection<int, Appointment>  $appointments
     * @return array<string, float|int>
     */
    private function buildAcquisitionSummary(Collection $pageVisits, Collection $leads, Collection $appointments): array
    {
        $whatsappLeads = $leads->where('source', TenantLead::SOURCE_WHATSAPP)->count();
        $generalContactLeads = $leads->where('source', TenantLead::SOURCE_CONTACT_GENERAL)->count();
        $quoteLeads = $leads->where('source', TenantLead::SOURCE_CONTACT_QUOTE)->count();
        $formLeads = $generalContactLeads + $quoteLeads;
        $engagedLeads = $whatsappLeads + $formLeads;
        $uniqueVisits = (int) $pageVisits->sum('unique_visits');
        $bookedAppointments = $appointments->count();

        return [
            'total_visits' => (int) $pageVisits->sum('visits'),
            'unique_visits' => $uniqueVisits,
            'whatsapp_leads' => $whatsappLeads,
            'general_contact_leads' => $generalContactLeads,
            'quote_leads' => $quoteLeads,
            'form_leads' => $formLeads,
            'engaged_leads' => $engagedLeads,
            'booked_appointments' => $bookedAppointments,
            'visit_to_engaged_rate' => $this->percentage($engagedLeads, $uniqueVisits),
            'visit_to_booking_rate' => $this->percentage($bookedAppointments, $uniqueVisits),
        ];
    }

    /**
     * @param  Collection<int, PageVisit>  $pageVisits
     * @param  Collection<int, TenantLead>  $leads
     * @param  Collection<int, Appointment>  $appointments
     * @param  array{start: Carbon, end: Carbon}  $range
     * @return list<array<string, int|string>>
     */
    private function buildAcquisitionDailySeries(Collection $pageVisits, Collection $leads, Collection $appointments, array $range): array
    {
        $visitsByDate = $pageVisits->keyBy(fn (PageVisit $visit): string => $visit->date->toDateString());
        $leadsByDate = $leads->groupBy(fn (TenantLead $lead): string => ($lead->occurred_at ?? $lead->created_at)->toDateString());
        $appointmentsByDate = $appointments->groupBy(fn (Appointment $appointment): string => $appointment->created_at->toDateString());

        return collect(CarbonPeriod::create($range['start']->toDateString(), $range['end']->toDateString()))
            ->map(function (CarbonInterface $date) use ($appointmentsByDate, $leadsByDate, $visitsByDate): array {
                $dateKey = $date->toDateString();
                /** @var PageVisit|null $visit */
                $visit = $visitsByDate->get($dateKey);
                /** @var Collection<int, TenantLead> $dayLeads */
                $dayLeads = $leadsByDate->get($dateKey, collect());
                /** @var Collection<int, Appointment> $dayAppointments */
                $dayAppointments = $appointmentsByDate->get($dateKey, collect());

                return [
                    'date' => $dateKey,
                    'label' => $date->locale('es_CL')->translatedFormat('d M'),
                    'visits' => (int) ($visit?->visits ?? 0),
                    'unique_visits' => (int) ($visit?->unique_visits ?? 0),
                    'whatsapp_leads' => $dayLeads->where('source', TenantLead::SOURCE_WHATSAPP)->count(),
                    'form_leads' => $dayLeads
                        ->whereIn('source', [TenantLead::SOURCE_CONTACT_GENERAL, TenantLead::SOURCE_CONTACT_QUOTE])
                        ->count(),
                    'booked_appointments' => $dayAppointments->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TenantLead>  $leads
     * @param  Collection<int, Appointment>  $appointments
     * @return list<array<string, string|int|null>>
     */
    private function buildRecentAcquisitionActivity(Collection $leads, Collection $appointments): array
    {
        $leadActivity = $leads->map(function (TenantLead $lead): array {
            $metadata = is_array($lead->metadata) ? $lead->metadata : [];

            return [
                'source' => $lead->source,
                'source_label' => $this->leadSourceLabel($lead->source),
                'visitor_name' => $lead->visitor_name ?: 'Visitante sin nombre',
                'contact' => $lead->phone ?: $lead->email ?: 'Sin contacto',
                'detail' => $this->leadActivityDetail($lead, $metadata),
                'occurred_at' => ($lead->occurred_at ?? $lead->created_at)->toISOString(),
                'sort_at' => ($lead->occurred_at ?? $lead->created_at)->timestamp,
            ];
        });

        $appointmentActivity = $appointments->map(function (Appointment $appointment): array {
            $schedule = $appointment->appointment_date->locale('es_CL')->translatedFormat('d M H:i');

            return [
                'source' => 'booking',
                'source_label' => 'Cita agendada',
                'visitor_name' => $appointment->customer_name ?: 'Cliente sin nombre',
                'contact' => $appointment->phone ?: 'Sin telefono',
                'detail' => "Patente {$appointment->plate} · {$schedule}",
                'occurred_at' => $appointment->created_at->toISOString(),
                'sort_at' => $appointment->created_at->timestamp,
            ];
        });

        return $leadActivity
            ->merge($appointmentActivity)
            ->sortByDesc('sort_at')
            ->take(12)
            ->values()
            ->map(function (array $activity): array {
                unset($activity['sort_at']);

                return $activity;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function leadActivityDetail(TenantLead $lead, array $metadata): string
    {
        return match ($lead->source) {
            TenantLead::SOURCE_WHATSAPP => 'Click al boton de WhatsApp desde la landing.',
            TenantLead::SOURCE_CONTACT_QUOTE => 'Solicitud de cotizacion'.(filled($metadata['plate'] ?? null) ? " · {$metadata['plate']}" : ''),
            default => filled($metadata['message'] ?? null)
                ? Str::limit((string) $metadata['message'], 80)
                : 'Formulario de contacto general.',
        };
    }

    private function leadSourceLabel(string $source): string
    {
        return match ($source) {
            TenantLead::SOURCE_WHATSAPP => 'WhatsApp',
            TenantLead::SOURCE_CONTACT_GENERAL => 'Formulario contacto',
            TenantLead::SOURCE_CONTACT_QUOTE => 'Formulario cotizacion',
            default => $source,
        };
    }

    private function percentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return (float) round(($value / $total) * 100, 1);
    }
}
