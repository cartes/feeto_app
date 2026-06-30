<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TrialRequestSubmitted;
use App\Models\User;
use App\Notifications\NewTrialRequestNotification;
use Illuminate\Support\Facades\Notification;

class NotifySuperAdminOfTrialRequest
{
    public function handle(TrialRequestSubmitted $event): void
    {
        /** @var User|null $superAdmin */
        $superAdmin = User::query()->where('is_super_admin', true)->first();

        if (! $superAdmin) {
            return;
        }

        Notification::route('mail', $superAdmin->email)
            ->notify(new NewTrialRequestNotification($event->trialRequest));
    }
}
