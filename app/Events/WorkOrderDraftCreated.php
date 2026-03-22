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
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderDraftCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkOrder $workOrder;
    public int $tenantId;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
        // Capturar el Tenant actual, pues Broadcasts saltan colas a veces.
        $this->tenantId = Tenant::current() ? (int) Tenant::current()->id : $workOrder->tenant_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Emitir a los trabajadores del taller (Usamos tenant para aislar evento)
        return [
            new Channel('tenant.' . $this->tenantId . '.work-orders'),
        ];
    }
}
