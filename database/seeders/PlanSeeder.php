<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Services\PlanFeatureService;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Para talleres pequeños que están comenzando.',
                'price_monthly' => 19990,
                'price_annual' => 199900,
                'max_users' => 2,
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'features' => [
                    'Hasta 2 usuarios',
                    'Módulo de órdenes de trabajo',
                    'Seguimiento de vehículos',
                    'Soporte por email',
                ],
                'feature_keys' => [],
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'El plan más popular para talleres en crecimiento.',
                'price_monthly' => 39990,
                'price_annual' => 399900,
                'max_users' => 5,
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
                'features' => [
                    'Hasta 5 usuarios',
                    'Todo lo del plan Básico',
                    'Lectura de patentes con IA',
                    'Módulo de citas',
                    'Inventario de repuestos',
                    'Módulo de clientes',
                    'Cotizaciones comerciales y aprobación del cliente',
                    'Soporte prioritario',
                ],
                'feature_keys' => [
                    PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                ],
            ],
            [
                'name' => 'Empresarial',
                'slug' => 'empresarial',
                'description' => 'Para talleres de alto volumen o cadenas.',
                'price_monthly' => 79990,
                'price_annual' => 799900,
                'max_users' => 20,
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'features' => [
                    'Hasta 20 usuarios',
                    'Todo lo del plan Profesional',
                    'Notificaciones WhatsApp',
                    'Recepción inteligente',
                    'Cotizaciones comerciales y aprobación del cliente',
                    'Reportes avanzados',
                    'API acceso',
                    'Soporte 24/7',
                ],
                'feature_keys' => [
                    PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                    PlanFeatureService::FEATURE_COMMERCIAL_REPORTS,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
