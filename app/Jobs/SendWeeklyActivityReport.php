<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WeeklyActivityReportNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendWeeklyActivityReport implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        /** @var User|null $superAdmin */
        $superAdmin = User::query()->where('is_super_admin', true)->first();

        if (! $superAdmin) {
            return;
        }

        $weekStart = now()->subWeek()->startOfDay();
        $weekEnd = now()->endOfDay();

        $totalActiveTenants = Tenant::query()->where('is_active', true)->count();
        $newTenants = Tenant::query()->whereBetween('created_at', [$weekStart, $weekEnd])->count();
        $newTrialRequests = TrialRequest::query()->whereBetween('created_at', [$weekStart, $weekEnd])->count();
        $pendingTrialRequests = TrialRequest::query()->where('status', 'pending')->count();
        $newWorkOrders = WorkOrder::withoutGlobalScope('tenant')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();
        $expiringTenants = Tenant::query()
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(14)])
            ->where('is_active', true)
            ->count();

        /** @var array<string, int> $metrics */
        $metrics = [
            'total_active_tenants' => $totalActiveTenants,
            'new_tenants' => $newTenants,
            'new_trial_requests' => $newTrialRequests,
            'pending_trial_requests' => $pendingTrialRequests,
            'new_work_orders' => $newWorkOrders,
            'expiring_tenants' => $expiringTenants,
            'week_start' => $weekStart->format('d/m/Y'),
            'week_end' => $weekEnd->format('d/m/Y'),
        ];

        Notification::route('mail', $superAdmin->email)
            ->notify(new WeeklyActivityReportNotification($metrics));
    }
}
