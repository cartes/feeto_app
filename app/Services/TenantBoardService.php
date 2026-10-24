<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Str;

class TenantBoardService
{
    private const LOW_STOCK_LIMIT = 8;

    private const STATUS_LABELS = [
        WorkOrder::STATUS_RECEPCION => 'Recepción',
        WorkOrder::STATUS_DIAGNOSTICO => 'En diagnóstico',
        'taller' => 'En taller',
        'aviso_cliente' => 'Avisar al cliente',
        WorkOrder::STATUS_ESPERANDO_REPUESTOS => 'Esperando repuestos',
        WorkOrder::STATUS_CONTROL_CALIDAD => 'Control de calidad',
        WorkOrder::STATUS_LISTO => 'Listo',
    ];

    public function __construct(private BranchContext $branchContext) {}

    /**
     * Datos del pizarrón: vehículos en taller por etapa y productos bajo stock.
     *
     * @return array{workshop: array{total: int, stages: list<array<string, mixed>>, vehicles: list<array<string, mixed>>}|null, low_stock: array{total: int, items: list<array<string, mixed>>}|null}
     */
    public function forTenant(Tenant $tenant, User $user): array
    {
        $routeParams = ['tenantBySlug' => $tenant->slug];

        return [
            'workshop' => $user->can('work-orders.view') || $user->can('work-orders.view-own')
                ? $this->workshop($routeParams)
                : null,
            'low_stock' => $user->can('inventory.manage')
                ? $this->lowStock($routeParams)
                : null,
        ];
    }

    /**
     * @param  array<string, string>  $routeParams
     * @return array{total: int, stages: list<array<string, mixed>>, vehicles: list<array<string, mixed>>}
     */
    private function workshop(array $routeParams): array
    {
        $branchId = $this->branchContext->id();

        $orders = WorkOrder::query()
            ->with('vehicle')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('created_at')
            ->get();

        $counts = $orders->countBy('status');
        $stageKeys = collect(WorkOrder::statuses())->merge($counts->keys())->unique()->values();

        return [
            'total' => $orders->count(),
            'stages' => $stageKeys->map(fn (string $status): array => [
                'status' => $status,
                'label' => self::STATUS_LABELS[$status] ?? Str::headline($status),
                'count' => (int) $counts->get($status, 0),
            ])->all(),
            'vehicles' => $orders->map(fn (WorkOrder $order): array => [
                'id' => $order->id,
                'plate' => $order->vehicle?->plate ?? 'N/A',
                'vehicle' => trim(($order->vehicle?->brand ?? '').' '.($order->vehicle?->model ?? '')),
                'status' => $order->status,
                'status_label' => self::STATUS_LABELS[$order->status] ?? Str::headline($order->status),
                'since' => $order->created_at->toISOString(),
                'url' => route('work-orders.show', [...$routeParams, 'workOrder' => $order->id]),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, string>  $routeParams
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function lowStock(array $routeParams): array
    {
        $branchId = $this->branchContext->id();

        $query = Product::query()
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->whereColumn('physical_stock', '<=', 'min_stock');

        return [
            'total' => (clone $query)->count(),
            'items' => $query->orderBy('physical_stock')->orderBy('name')->limit(self::LOW_STOCK_LIMIT)->get()
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'physical_stock' => $product->physical_stock,
                    'min_stock' => $product->min_stock,
                    'depleted' => $product->physical_stock <= 0,
                    'url' => route('inventory.index', [...$routeParams, 'search' => $product->sku]),
                ])->all(),
        ];
    }
}
