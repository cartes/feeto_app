<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRoleRequest;
use App\Http\Requests\UpdateTenantRoleRequest;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRoleController extends Controller
{
    /**
     * System roles that cannot be deleted or modified.
     *
     * @var list<string>
     */
    private const SYSTEM_ROLES = ['Admin', 'Recepcionista', 'Supervisor', 'Jefe', 'Mecanico'];

    /**
     * Permission groups for the frontend form, ordered by module.
     *
     * @var array<string, array{label: string, permissions: list<string>}>
     */
    private const PERMISSION_GROUPS = [
        'appointments' => [
            'label' => 'Agenda',
            'permissions' => ['appointments.manage'],
        ],
        'customers' => [
            'label' => 'Clientes y Vehículos',
            'permissions' => ['customers.manage', 'vehicles.manage'],
        ],
        'work_orders' => [
            'label' => 'Órdenes de Trabajo',
            'permissions' => [
                'work-orders.view',
                'work-orders.view-own',
                'work-orders.update-status',
                'work-orders.manage-items',
            ],
        ],
        'inventory' => [
            'label' => 'Inventario',
            'permissions' => ['inventory.manage'],
        ],
        'financials' => [
            'label' => 'Financiero',
            'permissions' => ['financials.view'],
        ],
        'reports' => [
            'label' => 'Reportes',
            'permissions' => ['reports.view'],
        ],
        'users' => [
            'label' => 'Usuarios',
            'permissions' => ['users.manage'],
        ],
        'branches' => [
            'label' => 'Sucursales',
            'permissions' => ['branches.manage'],
        ],
        'roles' => [
            'label' => 'Roles',
            'permissions' => ['roles.manage'],
        ],
    ];

    public function index(): Response
    {
        $tenant = Tenant::current();
        $canManageRoles = $tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES);

        $roles = Role::query()
            ->with('permissions')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'is_system' => in_array($role->name, self::SYSTEM_ROLES, true),
            ]);

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'canManageRoles' => $canManageRoles,
            'permissionGroups' => self::PERMISSION_GROUPS,
        ]);
    }

    public function create(): Response
    {
        $tenant = Tenant::current();
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, 'Tu plan no permite crear roles personalizados.');

        return Inertia::render('Settings/Roles/Create', [
            'permissionGroups' => self::PERMISSION_GROUPS,
        ]);
    }

    public function store(StoreTenantRoleRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, 'Tu plan no permite crear roles personalizados.');

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions'));

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$role->name}\" creado correctamente.");
    }

    public function edit(Role $role): Response
    {
        $tenant = Tenant::current();
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, 'Tu plan no permite editar roles personalizados.');
        abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'Los roles del sistema no pueden ser modificados.');

        return Inertia::render('Settings/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ],
            'permissionGroups' => self::PERMISSION_GROUPS,
        ]);
    }

    public function update(UpdateTenantRoleRequest $request, Role $role): RedirectResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, 'Tu plan no permite editar roles personalizados.');
        abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'Los roles del sistema no pueden ser modificados.');

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions'));

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$role->name}\" actualizado correctamente.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_ROLES), 403, 'Tu plan no permite eliminar roles personalizados.');
        abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'Los roles del sistema no pueden ser eliminados.');

        $roleName = $role->name;
        $role->delete();

        return redirect()
            ->route('taller.roles.index', ['tenantBySlug' => $tenant->slug])
            ->with('success', "Rol \"{$roleName}\" eliminado correctamente.");
    }
}
