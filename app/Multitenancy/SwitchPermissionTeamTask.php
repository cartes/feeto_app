<?php

declare(strict_types=1);

namespace App\Multitenancy;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Permission\PermissionRegistrar;

class SwitchPermissionTeamTask implements SwitchTenantTask
{
    public function __construct(protected PermissionRegistrar $permissionRegistrar) {}

    public function makeCurrent(IsTenant $tenant): void
    {
        $this->permissionRegistrar->setPermissionsTeamId($tenant->id);
    }

    public function forgetCurrent(): void
    {
        $this->permissionRegistrar->setPermissionsTeamId(null);
    }
}
