<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpsertProductRequest;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UpsertProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()->route('inventory.index')->with('success', 'Repuesto agregado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpsertProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->back()->with('success', 'Repuesto actualizado.');
    }

    /**
     * Display the specified resource.
     */
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

        // Return JSON explicitly since the frontend might request it via API, or we can just render the page if it's a direct visit.
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('inventory.index')->with('success', 'Repuesto eliminado.');
    }
}
