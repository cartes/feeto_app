<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'repuesto_nacional',
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'description' => fake()->sentence(),
            'cost_price' => fake()->randomFloat(2, 1000, 50000),
            'selling_price' => fake()->randomFloat(2, 2000, 80000),
            'physical_stock' => fake()->numberBetween(0, 100),
            'reserved_stock' => 0,
            'min_stock' => fake()->numberBetween(1, 10),
        ];
    }
}
