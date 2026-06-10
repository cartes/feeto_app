<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rut' => fake()->unique()->numerify('##.###.###-#'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('+569########'),
            'email' => fake()->unique()->safeEmail(),
            'max_credit_limit' => fake()->numberBetween(0, 500000),
        ];
    }
}
