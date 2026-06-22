<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpsertProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::withCount('products')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($categories);
    }

    public function store(UpsertProductCategoryRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        ProductCategory::create([
            'name' => $request->validated('name'),
            'slug' => ProductCategory::generateUniqueSlug($request->validated('name'), $tenantId),
        ]);

        return redirect()->back()->with('success', 'Categoría creada exitosamente.');
    }

    public function update(UpsertProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        $productCategory->update([
            'name' => $request->validated('name'),
            'slug' => ProductCategory::generateUniqueSlug($request->validated('name'), $tenantId),
        ]);

        return redirect()->back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $productCategory->delete();

        return redirect()->back()->with('success', 'Categoría eliminada.');
    }
}
