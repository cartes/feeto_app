<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRoleRequest;
use App\Http\Requests\UpdateTenantRoleRequest;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
use App\Services\TenantRoleCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantRoleController extends Controller
{
    public function index(): Response
    {
        $tenant = Tenant::current();
        $canManageRoles = $tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES);

        $roles = $this->visibleRolesForTenant($tenant)
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'is_system' => TenantRoleCatalog::isSystemRole($role->name),
            ]);

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'canManageRoles' => $canManageRoles,
            'permissionGroups' => TenantRoleCatalog::permissionGroups(),
            ...$this->settingsNavigationData($tenant),
        ]);
    }

    public function create(): Response
    {
        $tenant = Tenant::current();
        $this->ensureCustomRolesFeatureEnabled($tenant, 'Tu plan no permite crear roles personalizados.');

        return Inertia::render('Settings/Roles/Create', [
            'permissionGroups' => TenantRoleCatalog::permissionGroups(),
            ...$this->settingsNavigationData($tenant),
        ]);
    }

    public function store(StoreTenantRoleRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();
        $this->ensureCustomRolesFeatureEnabled($tenant, 'Tu plan no permite crear roles personalizados.');

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions'));

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$role->name}\" creado correctamente.");
    }

    public function edit(int|string $role): Response
    {
        $tenant = Tenant::current();
        $this->ensureCustomRolesFeatureEnabled($tenant, 'Tu plan no permite editar roles personalizados.');

        $role = $this->findVisibleRoleOrFail($tenant, $role);

        return Inertia::render('Settings/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'is_system' => TenantRoleCatalog::isSystemRole($role->name),
            ],
            'permissionGroups' => TenantRoleCatalog::permissionGroups(),
            ...$this->settingsNavigationData($tenant),
        ]);
    }

    public function update(UpdateTenantRoleRequest $request, int|string $role): RedirectResponse
    {
        $tenant = Tenant::current();
        $this->ensureCustomRolesFeatureEnabled($tenant, 'Tu plan no permite editar roles personalizados.');

        $role = $this->findVisibleRoleOrFail($tenant, $role);
        $isSystemRole = TenantRoleCatalog::isSystemRole($role->name);
        $role = $this->resolveWritableRole($tenant, $role);

        if (! $isSystemRole) {
            $role->update(['name' => $request->validated('name')]);
        }

        $role->syncPermissions($request->validated('permissions'));

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$role->name}\" actualizado correctamente.");
    }

    public function destroy(int|string $role): RedirectResponse
    {
        $tenant = Tenant::current();
        $this->ensureCustomRolesFeatureEnabled($tenant, 'Tu plan no permite eliminar roles personalizados.');

        $role = $this->findVisibleRoleOrFail($tenant, $role);
        $this->ensureRoleCanBeDeleted($role);

        $roleName = $role->name;
        $role->delete();

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$roleName}\" eliminado correctamente.");
    }

    private function ensureCustomRolesFeatureEnabled(Tenant $tenant, string $message): void
    {
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, $message);
    }

    private function ensureRoleCanBeDeleted(Role $role): void
    {
        abort_if(TenantRoleCatalog::isSystemRole($role->name), 403, 'Los roles del sistema no pueden ser eliminados.');
    }

    /**
     * @return array<string, int>
     */
    private function settingsNavigationData(Tenant $tenant): array
    {
        return [
            'planMaxUsers' => $tenant->userLimit(),
            'currentUserCount' => User::query()
                ->where('tenant_id', $tenant->id)
                ->count(),
            'branchesCount' => Branch::query()->count(),
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function visibleRolesForTenant(Tenant $tenant): Collection
    {
        $tenantRoles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->where('tenant_id', $tenant->id)
            ->get();

        $globalSystemRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereNull('tenant_id')
            ->whereIn('name', TenantRoleCatalog::systemRoles())
            ->when(
                $tenantRoles->isNotEmpty(),
                fn (Builder $query) => $query->whereNotIn('name', $tenantRoles->pluck('name')->all()),
            )
            ->with('permissions')
            ->get();

        $systemRoleOrder = array_flip(TenantRoleCatalog::systemRoles());

        return $tenantRoles
            ->concat($globalSystemRoles)
            ->sortBy(
                fn (Role $role): string => sprintf(
                    '%02d-%s',
                    $systemRoleOrder[$role->name] ?? 99,
                    $role->name,
                ),
            )
            ->values();
    }

    private function findVisibleRoleOrFail(Tenant $tenant, int|string $roleId): Role
    {
        $tenantRole = Role::query()
            ->where('guard_name', 'web')
            ->where('tenant_id', $tenant->id)
            ->with('permissions')
            ->find($roleId);

        if ($tenantRole instanceof Role) {
            return $tenantRole;
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->whereNull('tenant_id')
            ->whereIn('name', TenantRoleCatalog::systemRoles())
            ->with('permissions')
            ->findOrFail($roleId);
    }

    private function resolveWritableRole(Tenant $tenant, Role $role): Role
    {
        if (
            (int) $role->getAttribute('tenant_id') === (int) $tenant->id
            || ! TenantRoleCatalog::isSystemRole($role->name)
        ) {
            return $role;
        }

        return Role::query()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => $role->name,
            'guard_name' => 'web',
        ]);
    }
}
