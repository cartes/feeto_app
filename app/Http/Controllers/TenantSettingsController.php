<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTenantCommercialSettingsRequest;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BranchLimitService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantSettingsController extends Controller
{
    public function __construct(protected BranchLimitService $branchLimitService) {}

    /**
     * Muestra la página unificada de configuración del taller.
     * Incluye gestión de usuarios, sucursales y datos del tenant.
     */
    public function index(): Response
    {
        $tenant = Tenant::current();

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->orderBy('created_at')
            ->get(['id', 'name', 'email', 'created_at']);

        $branches = Branch::query()
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address', 'phone', 'email', 'is_main', 'is_active']);

        $roles = Role::query()
            ->whereIn('name', ['Admin', 'Recepcionista', 'Supervisor', 'Jefe', 'Mecanico'])
            ->get(['id', 'name']);

        return Inertia::render('Settings/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
            ]),
            'branches' => $branches,
            'roles' => $roles,
            'planMaxUsers' => $tenant->userLimit(),
            'currentUserCount' => $users->count(),
            'canCreateBranch' => $this->branchLimitService->canCreateBranch($tenant),
            'branchLimitInfo' => $this->branchLimitService->getLimitMessage($tenant),
            'tenant' => [
                ...$tenant->only('id', 'name', 'slug'),
                'plan' => $tenant->currentPlan()->value,
                'plan_label' => $tenant->currentPlan()->label(),
                'max_discount_without_approval' => $tenant->maxDiscountWithoutApproval(),
            ],
        ]);
    }

    public function updateCommercial(UpdateTenantCommercialSettingsRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();

        $tenant->update([
            'max_discount_without_approval' => $request->validated('max_discount_without_approval'),
        ]);

        return back()->with('success', 'La política comercial del taller fue actualizada.');
    }
}
