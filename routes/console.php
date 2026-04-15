<?php

use App\Jobs\NotifyExpiringSubscriptions;
use App\Jobs\SuspendExpiredTenants;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SuspendExpiredTenants)->daily();
Schedule::job(new NotifyExpiringSubscriptions)->dailyAt('08:00');
