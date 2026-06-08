<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReportController extends Controller
{
    public function __construct(protected PlanFeatureService $planFeatureService) {}

    public function index(): Response
    {
        $tenant = Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS));
        }

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

        return Inertia::render('Reports/Inventory', [
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
                ->values(),
            'lowStockProducts' => $lowStockProducts
                ->take(8)
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values(),
            'reservedProducts' => $reservedProducts
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values(),
            'highValueProducts' => $highValueProducts
                ->map(fn (Product $product): array => $this->serializeProduct($product))
                ->values(),
        ]);
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
}
