<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            // El vehículo debe pertenecer al mismo tenant que la orden
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'status' => WorkOrder::STATUS_RECEPCION,
            'observations' => fake()->optional()->sentence(),
            'total_amount' => fake()->numberBetween(10000, 500000),
        ];
    }

    public function listo(): static
    {
        return $this->state(['status' => WorkOrder::STATUS_LISTO]);
    }
}
