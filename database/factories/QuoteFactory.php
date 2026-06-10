<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            // La orden de trabajo debe pertenecer al mismo tenant que la cotización
            'work_order_id' => fn (array $attributes) => WorkOrder::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'client_id' => null,
            'vehicle_id' => null,
            'status' => Quote::STATUS_DRAFT,
            'subtotal_amount' => fake()->numberBetween(10000, 500000),
            'notes' => fake()->optional()->sentence(),
            'sent_at' => null,
            'responded_at' => null,
            'customer_response_notes' => null,
        ];
    }

    /** Cotización manual: asociada a cliente/vehículo, sin orden de trabajo */
    public function manual(): static
    {
        return $this->state([
            'work_order_id' => null,
            'client_id' => fn (array $attributes) => Client::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'client_id' => $attributes['client_id'],
            ])->id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => Quote::STATUS_ACCEPTED,
            'sent_at' => now()->subDay(),
            'responded_at' => now(),
        ]);
    }
}
