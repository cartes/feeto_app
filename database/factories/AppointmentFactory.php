<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => null,
            'vehicle_id' => null,
            'plate' => strtoupper(fake()->bothify('????##')),
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('+569########'),
            'appointment_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'pre_check_notes' => null,
        ];
    }

    public function arrived(): static
    {
        return $this->state(['status' => 'arrived']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
