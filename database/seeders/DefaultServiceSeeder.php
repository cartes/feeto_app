<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultServiceSeeder extends Seeder
{
    /**
     * Servicios predeterminados para cualquier taller mecánico nuevo.
     *
     * @var list<array{name: string, code: string, description: string, cost_price: int, selling_price: int, estimated_minutes: int}>
     */
    private const DEFAULT_SERVICES = [
        [
            'name' => 'Cambio de Aceite y Filtro',
            'code' => 'SRV-ACEITE',
            'description' => 'Cambio de aceite de motor y filtro de aceite. Incluye revisión de niveles.',
            'cost_price' => 15000,
            'selling_price' => 25000,
            'estimated_minutes' => 30,
        ],
        [
            'name' => 'Alineación y Balanceo',
            'code' => 'SRV-ALINBAL',
            'description' => 'Alineación computarizada de dirección y balanceo de las 4 ruedas.',
            'cost_price' => 18000,
            'selling_price' => 30000,
            'estimated_minutes' => 45,
        ],
        [
            'name' => 'Cambio de Pastillas de Freno',
            'code' => 'SRV-FRENOS',
            'description' => 'Reemplazo de pastillas de freno delanteras o traseras. Incluye inspección de discos.',
            'cost_price' => 20000,
            'selling_price' => 35000,
            'estimated_minutes' => 60,
        ],
        [
            'name' => 'Mantención Preventiva',
            'code' => 'SRV-MANT-PREV',
            'description' => 'Revisión general de 20 puntos: aceite, filtros, frenos, suspensión, luces y niveles.',
            'cost_price' => 30000,
            'selling_price' => 55000,
            'estimated_minutes' => 90,
        ],
        [
            'name' => 'Diagnóstico Electrónico (Scanner OBD)',
            'code' => 'SRV-SCANNER',
            'description' => 'Lectura y borrado de códigos de falla con scanner OBD-II. Informe escrito.',
            'cost_price' => 10000,
            'selling_price' => 20000,
            'estimated_minutes' => 30,
        ],
        [
            'name' => 'Cambio de Correa de Distribución',
            'code' => 'SRV-DISTRIB',
            'description' => 'Reemplazo de correa de distribución, tensor y rodillo guía.',
            'cost_price' => 60000,
            'selling_price' => 120000,
            'estimated_minutes' => 240,
        ],
        [
            'name' => 'Recarga de Aire Acondicionado',
            'code' => 'SRV-AC',
            'description' => 'Recarga de gas refrigerante R134a, detección de fugas y revisión del sistema A/C.',
            'cost_price' => 20000,
            'selling_price' => 40000,
            'estimated_minutes' => 60,
        ],
        [
            'name' => 'Cambio de Neumáticos',
            'code' => 'SRV-NEUMAT',
            'description' => 'Desmontaje, montaje y balanceo de neumáticos (precio por unidad).',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'estimated_minutes' => 20,
        ],
        [
            'name' => 'Cambio de Batería',
            'code' => 'SRV-BATERIA',
            'description' => 'Instalación de batería nueva con revisión del sistema de carga y arranque.',
            'cost_price' => 8000,
            'selling_price' => 15000,
            'estimated_minutes' => 20,
        ],
        [
            'name' => 'Cambio de Amortiguadores',
            'code' => 'SRV-AMORT',
            'description' => 'Reemplazo de amortiguadores delanteros o traseros (par). Incluye alineación básica.',
            'cost_price' => 35000,
            'selling_price' => 65000,
            'estimated_minutes' => 120,
        ],
    ];

    /**
     * Crea los servicios predeterminados para el tenant actualmente activo.
     * Si no hay tenant activo, los crea para todos los tenants existentes.
     */
    public function run(): void
    {
        $currentTenant = Tenant::current();

        if ($currentTenant) {
            $this->seedForTenant($currentTenant);

            return;
        }

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $this->seedForTenant($tenant);
        }

        Tenant::forgetCurrent();
    }

    /**
     * Inserta los servicios predeterminados para un tenant específico.
     * Usa firstOrCreate para evitar duplicados si se ejecuta más de una vez.
     */
    private function seedForTenant(Tenant $tenant): void
    {
        foreach (self::DEFAULT_SERVICES as $serviceData) {
            Service::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $serviceData['code'],
                ],
                array_merge($serviceData, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]),
            );
        }
    }
}
