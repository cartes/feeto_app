<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendRenewalReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Tenant::query()
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
            ->where('is_active', true)
            ->each(function (Tenant $tenant): void {
                $tenant->makeCurrent();

                $admin = User::where('tenant_id', $tenant->id)
                    ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                    ->first();

                Tenant::forgetCurrent();

                if (! $admin) {
                    AuditLog::record(
                        'subscription.renewal_reminder_skipped',
                        "No se encontró usuario Admin para '{$tenant->name}'. Recordatorio de renovación no enviado.",
                        $tenant
                    );

                    return;
                }

                $admin->notify(new SubscriptionRenewalReminder($tenant));

                AuditLog::record(
                    'subscription.renewal_reminder',
                    "Recordatorio de renovación enviado a {$admin->email} para '{$tenant->name}' (vence {$tenant->subscription_ends_at->format('d/m/Y')})",
                    $tenant
                );
            });
    }
}
