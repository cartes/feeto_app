<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DefaultServiceSeeder;
use Database\Seeders\TenantRolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

class TenantSetupService
{
    public function __construct(protected PermissionRegistrar $permissionRegistrar) {}

    /**
     * Seeds roles/permissions and default services for the given tenant
     * and optionally assigns the Admin role to a user.
     * Restores the previous tenant context after provisioning.
     */
    public function provisionTenant(Tenant $tenant, ?User $adminUser = null): void
    {
        $previousTenant = Tenant::current();

        $tenant->makeCurrent();

        $seeder = new TenantRolesAndPermissionsSeeder;
        $seeder->run();

        $serviceSeeder = new DefaultServiceSeeder;
        $serviceSeeder->run();

        $this->ensureMainBranch($tenant);

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

    /**
     * Asegura que el taller tenga su Casa Matriz creada.
     */
    public function ensureMainBranch(Tenant $tenant): Branch
    {
        $mainBranch = $tenant->branches()->where('is_main', true)->first()
            ?? $tenant->branches()->first();

        if (! $mainBranch) {
            $mainBranch = Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Casa Matriz',
                'code' => 'MATRIZ',
                'address' => $tenant->seo_address ?? $tenant->comuna ?? null,
                'phone' => $tenant->whatsapp_number ?? null,
                'is_main' => true,
                'is_active' => true,
            ]);
        }

        return $mainBranch;
    }
}
