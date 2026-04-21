<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TenantRolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

class TenantSetupService
{
    public function __construct(protected PermissionRegistrar $permissionRegistrar) {}

    /**
     * Seeds roles/permissions for the given tenant and optionally assigns the Admin role to a user.
     * Restores the previous tenant context after provisioning.
     */
    public function provisionTenant(Tenant $tenant, ?User $adminUser = null): void
    {
        $previousTenant = Tenant::current();

        $tenant->makeCurrent();

        $seeder = new TenantRolesAndPermissionsSeeder;
        $seeder->run();

        if ($adminUser !== null) {
            $this->assignAdminRole($adminUser);
        }

        if ($previousTenant && $previousTenant->id !== $tenant->id) {
            $previousTenant->makeCurrent();
        } elseif ($previousTenant === null) {
            Tenant::forgetCurrent();
        }
    }

    /**
     * Assigns the tenant-scoped Admin role to the given user.
     * The tenant must already be current when this is called.
     */
    public function assignAdminRole(User $adminUser): void
    {
        $adminUser->assignRole('Admin');
    }
}
