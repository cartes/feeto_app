<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

class WorkOrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public WorkOrder $workOrder,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        Log::info('Broadcasting WorkOrderStatusUpdated on channel: taller.' . $this->workOrder->tenant_id);
        return [
            new PrivateChannel('taller.' . $this->workOrder->tenant_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'WorkOrderStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'plate' => $this->workOrder->vehicle->plate,
            'vehicle' => $this->workOrder->vehicle->brand . ' ' . $this->workOrder->vehicle->model,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toISOString(),
        ];
    }
}
