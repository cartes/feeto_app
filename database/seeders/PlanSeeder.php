<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantPlan;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => TenantPlan::GRATUITO->label(),
                'slug' => TenantPlan::GRATUITO->value,
                'description' => 'Para talleres que están comenzando y necesitan operar con lo esencial.',
                'price_monthly' => 0,
                'price_annual' => 0,
                'max_users' => TenantPlan::GRATUITO->userLimit(),
                'trial_days' => 0,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'features' => [
                    'Hasta 2 usuarios',
                    'Operación base del taller',
                    'Acceso inicial al sistema',
                ],
                'feature_keys' => [],
            ],
            [
                'name' => TenantPlan::BASICO->label(),
                'slug' => TenantPlan::BASICO->value,
                'description' => 'Para talleres que quieren ordenar la operación diaria con herramientas base.',
                'price_monthly' => 19990,
                'price_annual' => 199900,
                'max_users' => TenantPlan::BASICO->userLimit(),
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
                'features' => [
                    'Hasta 5 usuarios',
                    'Recepción asistida por IA',
                    'Kanban personalizado',
                    'Soporte por email',
                ],
                'feature_keys' => TenantPlan::BASICO->featureKeys(),
            ],
            [
                'name' => TenantPlan::PROFESIONAL->label(),
                'slug' => TenantPlan::PROFESIONAL->value,
                'description' => 'El plan más popular para talleres en crecimiento.',
                'price_monthly' => 39990,
                'price_annual' => 399900,
                'max_users' => TenantPlan::PROFESIONAL->userLimit(),
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
                'features' => [
                    'Hasta 10 usuarios',
                    'Todo lo del plan Básico',
                    'Agenda en calendario',
                    'Inventario avanzado',
                    'Gestión de ventas',
                    'Soporte prioritario',
                ],
                'feature_keys' => TenantPlan::PROFESIONAL->featureKeys(),
            ],
            [
                'name' => TenantPlan::EMPRESA->label(),
                'slug' => TenantPlan::EMPRESA->value,
                'description' => 'Para talleres de alto volumen o cadenas.',
                'price_monthly' => 79990,
                'price_annual' => 799900,
                'max_users' => TenantPlan::EMPRESA->userLimit(),
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
                'features' => [
                    'Hasta 20 usuarios',
                    'Todo lo del plan Profesional',
                    'WhatsApp automático',
                    'Reportes avanzados',
                    'API acceso',
                    'Soporte 24/7',
                ],
                'feature_keys' => TenantPlan::EMPRESA->featureKeys(),
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
