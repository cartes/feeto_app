<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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

        $body = "Los siguientes talleres tienen suscripciones próximas a vencer:\n\n";

        foreach ($expiringTenants as $tenant) {
            $body .= "- {$tenant->name} (vence el {$tenant->subscription_ends_at->format('d/m/Y')})\n";
        }

        Mail::raw($body, function ($message) use ($superAdmin, $expiringTenants) {
            $message->to($superAdmin->email)
                ->subject("[Feeto] {$expiringTenants->count()} suscripción(es) próximas a vencer");
        });
    }
}
