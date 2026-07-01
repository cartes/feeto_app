<?php

use App\Jobs\NotifyExpiringSubscriptions;
use App\Jobs\QueueOverdueInvoiceWhatsAppReminders;
use App\Jobs\SendRenewalReminders;
use App\Jobs\SendWeeklyActivityReport;
use App\Jobs\SuspendExpiredTenants;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static fn () => app(SuspendExpiredTenants::class)->handle())
    ->name('SuspendExpiredTenants')
    ->daily();
Schedule::call(static fn () => app(NotifyExpiringSubscriptions::class)->handle())
    ->name('NotifyExpiringSubscriptions')
    ->dailyAt('08:00');
Schedule::job(new QueueOverdueInvoiceWhatsAppReminders)->dailyAt('09:00');
Schedule::call(static fn () => app(SendRenewalReminders::class)->handle())
    ->name('SendRenewalReminders')
    ->dailyAt('09:30');
Schedule::call(static fn () => app(SendWeeklyActivityReport::class)->handle())
    ->name('SendWeeklyActivityReport')
    ->weeklyOn(6, '12:00');
