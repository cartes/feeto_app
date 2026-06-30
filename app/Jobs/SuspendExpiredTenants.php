<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantSuspendedNotification;
use App\Notifications\TenantsSuspendedSummaryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SuspendExpiredTenants implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $suspended = collect();

        Tenant::query()
            ->where('subscription_ends_at', '<', now())
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->each(function (Tenant $tenant) use ($suspended): void {
                $tenant->update(['is_active' => false]);

                AuditLog::record(
                    'tenant.auto_suspended',
                    "Taller '{$tenant->name}' suspendido automáticamente por suscripción vencida.",
                    $tenant
                );

                $admin = User::where('tenant_id', $tenant->id)
                    ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                    ->first();

                if ($admin) {
                    $admin->notify(new TenantSuspendedNotification($tenant));
                }

                $suspended->push($tenant);
            });

        if ($suspended->isNotEmpty()) {
            $superAdmin = User::where('is_super_admin', true)->first();

            if ($superAdmin) {
                Notification::route('mail', $superAdmin->email)
                    ->notify(new TenantsSuspendedSummaryNotification($suspended));
            }
        }
    }
}
