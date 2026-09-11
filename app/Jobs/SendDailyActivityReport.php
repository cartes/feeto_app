<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LoginLog;
use App\Models\PageVisit;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DailyActivityReportNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendDailyActivityReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?string $targetEmail = null,
        public readonly bool $isTest = false
    ) {}

    /**
     * Ejecuta el reporte diario y envía la notificación por correo.
     *
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $recipient = $this->resolveRecipientEmail();

        if (empty($recipient)) {
            Log::warning('SendDailyActivityReport: No se encontró destinatario para enviar el reporte diario.');

            return [];
        }

        $metrics = $this->collectMetrics();

        Notification::route('mail', $recipient)
            ->notify(new DailyActivityReportNotification($metrics));

        Log::info("SendDailyActivityReport: Notificación enviada exitosamente a {$recipient}", [
            'is_test' => $this->isTest,
            'date' => $metrics['report_date'],
        ]);

        return $metrics;
    }

    /**
     * Recopila las métricas operativas de la plataforma correspondientes al día de hoy.
     *
     * @return array{
     *     report_date: string,
     *     total_active_tenants: int,
     *     new_tenants_today: int,
     *     new_trial_requests_today: int,
     *     pending_trial_requests: int,
     *     work_orders_today: int,
     *     quotes_today: int,
     *     logins_today: int,
     *     visits_today: int,
     *     expiring_tenants_count: int,
     *     expiring_tenants_list: array<int, string>,
     *     is_test: bool,
     * }
     */
    public function collectMetrics(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $totalActiveTenants = Tenant::query()->where('is_active', true)->count();
        $newTenantsToday = Tenant::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $newTrialRequestsToday = TrialRequest::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $pendingTrialRequests = TrialRequest::query()->where('status', 'pending')->count();

        $workOrdersToday = WorkOrder::withoutGlobalScope('tenant')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $quotesToday = Quote::withoutGlobalScope('tenant')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $loginsToday = LoginLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $visitsToday = (int) PageVisit::query()
            ->where('date', today())
            ->sum('visits');

        $expiringTenants = Tenant::query()
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
            ->where('is_active', true)
            ->get();

        $expiringTenantsList = $expiringTenants->map(static function (Tenant $t): string {
            $daysLeft = (int) max(0, now()->diffInDays($t->subscription_ends_at, false));
            $daysLabel = $daysLeft <= 0 ? 'vence hoy' : "vence en {$daysLeft} días";

            return "{$t->name} ({$daysLabel} - {$t->subscription_ends_at?->format('d/m/Y')})";
        })->all();

        return [
            'report_date' => now()->format('d/m/Y'),
            'total_active_tenants' => $totalActiveTenants,
            'new_tenants_today' => $newTenantsToday,
            'new_trial_requests_today' => $newTrialRequestsToday,
            'pending_trial_requests' => $pendingTrialRequests,
            'work_orders_today' => $workOrdersToday,
            'quotes_today' => $quotesToday,
            'logins_today' => $loginsToday,
            'visits_today' => $visitsToday,
            'expiring_tenants_count' => $expiringTenants->count(),
            'expiring_tenants_list' => $expiringTenantsList,
            'is_test' => $this->isTest,
        ];
    }

    /**
     * Resuelve el correo de destino para el informe.
     */
    public function resolveRecipientEmail(): ?string
    {
        if (! empty($this->targetEmail)) {
            return $this->targetEmail;
        }

        $configuredEmail = config('mail.admin_report_email');
        if (! empty($configuredEmail)) {
            return (string) $configuredEmail;
        }

        /** @var User|null $superAdmin */
        $superAdmin = User::query()->where('is_super_admin', true)->first();

        return $superAdmin?->email;
    }
}
