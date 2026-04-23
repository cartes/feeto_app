<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRolesAndPermissionsSeeder extends Seeder
{
    /**
     * All permissions grouped by resource.
     *
     * @var array<string, list<string>>
     */
    private const PERMISSIONS = [
        'appointments' => ['appointments.manage'],
        'customers' => ['customers.manage'],
        'vehicles' => ['vehicles.manage'],
        'work_orders' => [
            'work-orders.view',
            'work-orders.view-own',
            'work-orders.update-status',
            'work-orders.manage-items',
        ],
        'inventory' => ['inventory.manage'],
        'branches' => ['branches.manage'],
        'reports' => ['reports.view'],
        'financials' => ['financials.view'],
        'users' => ['users.manage'],
        'roles' => ['roles.manage'],
    ];

    /**
     * Permissions granted to each role (tenant-scoped).
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'Admin' => [
            'appointments.manage',
            'customers.manage',
            'vehicles.manage',
            'work-orders.view',
            'work-orders.view-own',
            'work-orders.update-status',
            'work-orders.manage-items',
            'inventory.manage',
            'branches.manage',
            'reports.view',
            'financials.view',
            'users.manage',
            'roles.manage',
        ],
        'Recepcionista' => [
            'appointments.manage',
            'customers.manage',
            'vehicles.manage',
            'work-orders.view',
            'financials.view',
        ],
        'Supervisor' => [
            'appointments.manage',
            'customers.manage',
            'vehicles.manage',
            'work-orders.view',
            'work-orders.update-status',
            'work-orders.manage-items',
            'inventory.manage',
            'reports.view',
            'financials.view',
        ],
        'Jefe' => [
            'appointments.manage',
            'customers.manage',
            'vehicles.manage',
            'work-orders.view',
            'work-orders.update-status',
            'work-orders.manage-items',
            'inventory.manage',
            'reports.view',
            'financials.view',
        ],
        'Mecanico' => [
            'work-orders.view-own',
            'work-orders.update-status',
            'work-orders.manage-items',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = array_merge(...array_values(self::PERMISSIONS));

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
