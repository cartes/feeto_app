<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\WorkOrderStatusUpdated;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendWorkOrderStatusChangedEmail implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle(WorkOrderStatusUpdated $event): void
    {
        if ($event->oldStatus === $event->newStatus) {
            return;
        }

        $workOrder = WorkOrder::withoutGlobalScope('tenant')
            ->with(['vehicle.client'])
            ->findOrFail($event->workOrder->id);

        if ($workOrder->hasBeenNotifiedFor($event->newStatus)) {
            return;
        }

        $client = $workOrder->vehicle?->client;
        $email = $client?->email;

        if (! filled($email)) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new WorkOrderStatusChangedNotification($workOrder, $event->oldStatus, $event->newStatus));

        $workOrder->markStatusAsNotified($event->newStatus);
    }
}
