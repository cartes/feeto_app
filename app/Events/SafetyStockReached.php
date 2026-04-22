<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SafetyStockReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Product $product,
        public Tenant $tenant
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('taller.'.$this->tenant->id),
        ];
    }
}
