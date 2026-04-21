<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantSetupService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DiagnosticRoles extends Command
{
    protected $signature = 'feeto:diagnose-roles {--fix : Asigna rol Admin al primer usuario de cada tenant si no tiene roles}';

    protected $description = 'Diagnostica y corrige los roles de usuarios por tenant';

    /**
     * Execute the console command.
     */
    public function handle(TenantSetupService $tenantSetupService): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenants = Tenant::with('users')->get();

        if ($tenants->isEmpty()) {
            $this->error('No hay tenants en la base de datos.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $this->line('');
            $this->info("Tenant: {$tenant->name} (slug: {$tenant->slug})");

            $rolesEnBd = Role::all();
            $this->line('   Roles en BD: '.$rolesEnBd->pluck('name')->implode(', '));

            foreach ($tenant->users as $user) {
                $roles = $user->roles->pluck('name')->implode(', ');
                $sinRoles = $roles === '';
                $this->line("   {$user->name} <{$user->email}> -> [{$roles}]".($sinRoles ? ' *** SIN ROLES ***' : ''));

                if ($this->option('fix') && $sinRoles) {
                    $tenantSetupService->provisionTenant($tenant, $user);
                    $user->refresh();
                    $newRoles = $user->roles->pluck('name')->implode(', ');
                    $this->warn("      Corregido -> [{$newRoles}]");
                }
            }

            Tenant::forgetCurrent();
        }

        $this->line('');

        return self::SUCCESS;
    }
}
