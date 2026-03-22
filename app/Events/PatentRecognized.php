<?php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Models\Tenant;

class PatentRecognized implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $patente;
    public string $imageUrl;
    public int $tenantId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patente, string $imageUrl)
    {
        $this->patente = $patente;
        $this->imageUrl = $imageUrl;
        // Capturamos el tenant actual para enviarlo solo a ese canal
        $this->tenantId = Tenant::current() ? (int) Tenant::current()->id : 0;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // En un SaaS real, normalmente usamos un canal privado por tenant.
        // Para simplificar la prueba inicial sin Auth de canales complejo,
        // Usaremos un canal público con el ID del tenant.
        return [
            new Channel('tenant.' . $this->tenantId . '.reception'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'patente' => $this->patente,
            'image_url' => $this->imageUrl,
        ];
    }
}
