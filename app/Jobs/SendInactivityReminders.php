<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInactivityReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class SendInactivityReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Tenant::query()
            ->where('is_active', true)
            ->each(function (Tenant $tenant): void {
                $lastLoginDate = LoginLog::where('tenant_id', $tenant->id)->max('created_at');
                $referenceDate = $lastLoginDate ? Carbon::parse($lastLoginDate) : $tenant->created_at;

                // Si ha ingresado o se creó hace menos de 10 días, no es inactivo
                if ($referenceDate->greaterThanOrEqualTo(now()->subDays(10))) {
                    return;
                }

                // Evitar spam: no enviar más de una vez cada 7 días
                $alreadySentRecently = AuditLog::query()
                    ->where('action', 'tenant.inactivity_reminder_sent')
                    ->where('model_type', Tenant::class)
                    ->where('model_id', $tenant->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->exists();

                if ($alreadySentRecently) {
                    return;
                }

                // Buscar el usuario Admin o primer usuario
                $tenant->makeCurrent();

                $admin = User::where('tenant_id', $tenant->id)
                    ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                    ->first()
                    ?? User::where('tenant_id', $tenant->id)->first();

                Tenant::forgetCurrent();

                if (! $admin) {
                    AuditLog::record(
                        'tenant.inactivity_reminder_skipped',
                        "No se encontró usuario para '{$tenant->name}'. Recordatorio de inactividad omitido.",
                        $tenant
                    );

                    return;
                }

                // Enviar la notificación
                $admin->notify(new TenantInactivityReminder($tenant));

                // Registrar en la bitácora
                $lastLoginStr = $lastLoginDate ? Carbon::parse($lastLoginDate)->format('d/m/Y') : 'nunca';
                AuditLog::record(
                    'tenant.inactivity_reminder_sent',
                    "Recordatorio de inactividad enviado a {$admin->email} para '{$tenant->name}' (último login: {$lastLoginStr})",
                    $tenant
                );
            });
    }
}
