<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define el estado por defecto del Tenant.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'domain' => Str::slug($name).'.tallerflow.cl',
            'rut_taller' => fake()->numerify('##.###.###-#'),
            'is_active' => true,
            'plan_type' => 'gratuito',
            'plan' => 'gratuito',
            'status' => 'active',
            'comuna' => 'Santiago',
            'phone' => '+56912345678',
            'seo_description' => 'Taller automotriz de confianza.',
        ];
    }

    /**
     * Taller sin los datos de perfil que completa el asistente de bienvenida.
     */
    public function withoutProfileData(): static
    {
        return $this->state(fn (): array => ['comuna' => null, 'phone' => null, 'seo_description' => null]);
    }
}
