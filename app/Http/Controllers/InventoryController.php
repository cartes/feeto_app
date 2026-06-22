<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpsertProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $type = $request->input('type');
        $stockStatus = $request->input('stock_status');
        $priceMin = $request->input('price_min');
        $priceMax = $request->input('price_max');

        $products = Product::query()
            ->with('category:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query, $category) => $query->where('category_id', $category))
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($stockStatus, function ($query, $status) {
                match ($status) {
                    'critical' => $query->where('physical_stock', '<=', 0),
                    'low' => $query->whereColumn('physical_stock', '<=', 'min_stock')
                        ->where('physical_stock', '>', 0),
                    'normal' => $query->whereColumn('physical_stock', '>', 'min_stock'),
                    default => null,
                };
            })
            ->when($priceMin, fn ($query, $min) => $query->where('selling_price', '>=', $min))
            ->when($priceMax, fn ($query, $max) => $query->where('selling_price', '<=', $max))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = ProductCategory::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'type' => $type,
                'stock_status' => $stockStatus,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
            ],
        ]);
    }

    public function store(UpsertProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        if ($product->physical_stock > 0) {
            app(StockService::class)->recordManualAdjustment($product, 0, $product->physical_stock);
        }

        return redirect()->route('inventory.index')->with('success', 'Repuesto agregado exitosamente.');
    }

    public function update(UpsertProductRequest $request, Product $product): RedirectResponse
    {
        $oldStock = $product->physical_stock;

        $product->update($request->validated());

        $newStock = (int) $request->validated()['physical_stock'];
        if ($oldStock !== $newStock) {
            app(StockService::class)->recordManualAdjustment($product, $oldStock, $newStock);
        }

        return redirect()->back()->with('success', 'Repuesto actualizado.');
    }

    public function show(Product $inventory)
    {
        $tenant = Tenant::current();
        $isAdvanced = $tenant?->hasFeature(PlanFeatureService::FEATURE_ADVANCED_INVENTORY) ?? false;

        $productType = $inventory->type ?? 'repuesto_nacional';

        $similares = Product::where('id', '!=', $inventory->id)
            ->where('type', $productType)
            ->limit(5)
            ->get();

        $relacionados = [];
        if ($isAdvanced) {
            $price = $inventory->selling_price;
            $minPrice = $price * 0.8;
            $maxPrice = $price * 1.2;

            $relacionados = Product::where('id', '!=', $inventory->id)
                ->whereBetween('selling_price', [$minPrice, $maxPrice])
                ->limit(5)
                ->get();
        }

        if (request()->wantsJson()) {
            return response()->json([
                'product' => $inventory,
                'similares' => $similares,
                'relacionados' => $relacionados,
            ]);
        }

        return Inertia::render('Inventory/Show', [
            'product' => $inventory,
            'similares' => $similares,
            'relacionados' => $relacionados,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('inventory.index')->with('success', 'Repuesto eliminado.');
    }

    public function movements(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()
            ->with('user:id,name')
            ->paginate(20);

        return response()->json($movements);
    }
}
