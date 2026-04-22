<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'price_monthly' => fake()->numberBetween(9990, 99990),
            'price_annual' => fake()->numberBetween(99990, 999990),
            'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
            'feature_keys' => [],
            'max_users' => fake()->numberBetween(2, 50),
            'trial_days' => 14,
            'is_active' => true,
            'is_popular' => false,
            'discount_percent' => 0,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
