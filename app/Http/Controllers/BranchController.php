<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Tenant;
use App\Services\BranchLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct(protected BranchLimitService $branchLimitService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $branches = Branch::query()
            ->when($search, function ($query, $search) {
                $escaped = addcslashes($search, '%_');
                $query->where('name', 'like', "%{$escaped}%")
                    ->orWhere('code', 'like', "%{$escaped}%");
            })
            ->orderBy('is_main', 'desc')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tenant = Tenant::current();
        $limitInfo = $tenant ? $this->branchLimitService->getLimitMessage($tenant) : '';

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters' => ['search' => $search],
            'limitInfo' => $limitInfo,
            'canCreate' => $tenant ? $this->branchLimitService->canCreateBranch($tenant) : false,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        if (! $this->branchLimitService->canCreateBranch($tenant)) {
            return redirect()
                ->route('branches.index')
                ->withErrors([
                    'branch' => 'Has alcanzado el límite de sucursales permitido por tu plan. '.$this->branchLimitService->getLimitMessage($tenant),
                ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        // If this branch is set as main, unset any existing main branch
        if (! empty($validated['is_main'])) {
            Branch::where('tenant_id', $tenant->id)
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Sucursal creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_main' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If this branch is set as main, unset any existing main branch
        if (! empty($validated['is_main']) && ! $branch->is_main) {
            Branch::where('tenant_id', $branch->tenant_id)
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        // Prevent deleting the main branch
        if ($branch->is_main) {
            return redirect()
                ->route('branches.index')
                ->withErrors(['branch' => 'No se puede eliminar la sucursal principal.']);
        }

        // Check if branch has associated records
        if ($branch->products()->exists() || $branch->workOrders()->exists()) {
            return redirect()
                ->route('branches.index')
                ->withErrors(['branch' => 'No se puede eliminar la sucursal porque tiene registros asociados.']);
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Sucursal eliminada.');
    }
}
