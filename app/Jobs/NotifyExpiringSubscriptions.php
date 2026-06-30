<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ExpiringSubscriptionsDigestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyExpiringSubscriptions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $expiringTenants = Tenant::query()
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
            ->where('is_active', true)
            ->get();

        if ($expiringTenants->isEmpty()) {
            return;
        }

        $superAdmin = User::where('is_super_admin', true)->first();

        if (! $superAdmin) {
            return;
        }

        Notification::route('mail', $superAdmin->email)
            ->notify(new ExpiringSubscriptionsDigestNotification($expiringTenants));
    }
}
