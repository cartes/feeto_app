<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SuspendExpiredTenants implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Tenant::query()
            ->where('subscription_ends_at', '<', now())
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->each(function (Tenant $tenant): void {
                $tenant->update(['is_active' => false]);

                AuditLog::record(
                    'tenant.auto_suspended',
                    "Taller '{$tenant->name}' suspendido automáticamente por suscripción vencida.",
                    $tenant
                );
            });
    }
}
