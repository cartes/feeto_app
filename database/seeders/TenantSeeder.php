<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $talleres = [
            [
                'name'       => 'Taller Automotriz Cartes',
                'domain'     => 'cartes',
                'rut_taller' => '76.543.210-1',
                'is_active'  => true,
            ],
            [
                'name'       => 'Full Mantenciones',
                'domain'     => 'full-mantenciones',
                'rut_taller' => '76.543.210-2',
                'is_active'  => true,
            ],
            [
                'name'       => 'Medelauto Servicio Automotriz',
                'domain'     => 'medelauto',
                'rut_taller' => '76.543.210-3',
                'is_active'  => true,
            ],
        ];

        foreach ($talleres as $taller) {
            Tenant::create($taller);
        }
    }
}
