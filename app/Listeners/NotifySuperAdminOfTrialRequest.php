<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TrialRequestSubmitted;
use App\Models\User;
use App\Notifications\NewTrialRequestNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class NotifySuperAdminOfTrialRequest
{
    public function handle(TrialRequestSubmitted $event): void
    {
        $cacheKey = "trial-request-notification:{$event->trialRequest->getKey()}";

        if (! Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return;
        }

        /** @var User|null $superAdmin */
        $superAdmin = User::query()->where('is_super_admin', true)->first();

        if (! $superAdmin) {
            Cache::forget($cacheKey);

            return;
        }

        try {
            Notification::route('mail', $superAdmin->email)
                ->notify(new NewTrialRequestNotification($event->trialRequest));
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);

            throw $exception;
        }
    }
}
