<?php

declare(strict_types=1);

namespace App\Services;

class TenantRoleCatalog
{
    /**
     * System roles that remain read-only inside each tenant.
     *
     * @var list<string>
     */
    private const SYSTEM_ROLES = ['Admin', 'Recepcionista', 'Supervisor', 'Jefe', 'Mecanico'];

    /**
     * Permission groups exposed in the tenant role UI.
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

    /**
     * @return list<string>
     */
    public static function systemRoles(): array
    {
        return self::SYSTEM_ROLES;
    }

    /**
     * @return array<string, array{label: string, permissions: list<string>}>
     */
    public static function permissionGroups(): array
    {
        return self::PERMISSION_GROUPS;
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $permissionNames = [];

        foreach (self::PERMISSION_GROUPS as $group) {
            foreach ($group['permissions'] as $permissionName) {
                $permissionNames[$permissionName] = $permissionName;
            }
        }

        return array_values($permissionNames);
    }

    public static function isSystemRole(string $roleName): bool
    {
        return in_array($roleName, self::SYSTEM_ROLES, true);
    }
}
