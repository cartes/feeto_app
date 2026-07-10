<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TrialRequestSubmitted;
use App\Notifications\ClientTrialRequestReceivedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyClientOfTrialRequest
{
    /**
     * Handle the event.
     */
    public function handle(TrialRequestSubmitted $event): void
    {
        Notification::route('mail', $event->trialRequest->email)
            ->notify(new ClientTrialRequestReceivedNotification($event->trialRequest));
    }
}
