<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            // El cliente debe pertenecer al mismo tenant que el vehículo
            'client_id' => fn (array $attributes) => Client::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'plate' => strtoupper(fake()->unique()->bothify('????##')),
            'brand' => fake()->randomElement(['Toyota', 'Chevrolet', 'Hyundai', 'Kia', 'Nissan', 'Suzuki']),
            'model' => fake()->word(),
            'color' => fake()->safeColorName(),
            'vin' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
        ];
    }
}
