<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantLead>
 */
class TenantLeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'source' => fake()->randomElement([
                TenantLead::SOURCE_WHATSAPP,
                TenantLead::SOURCE_CONTACT_GENERAL,
                TenantLead::SOURCE_CONTACT_QUOTE,
            ]),
            'channel' => 'landing_page',
            'visitor_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'metadata' => [
                'message' => fake()->sentence(),
                'landing_path' => 'taller/'.fake()->slug(),
            ],
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 14)),
        ];
    }
}
