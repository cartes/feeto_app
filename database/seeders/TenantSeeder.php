<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $database = config('database.connections.mysql.database');

        $talleres = [
            [
                'name' => 'Taller Automotriz Cartes',
                'slug' => 'cartes',
                'domain' => 'cartes',
                'database' => $database,
                'rut_taller' => '76.543.210-1',
                'is_active' => true,
            ],
            [
                'name' => 'Full Mantenciones',
                'slug' => 'full-mantenciones',
                'domain' => 'full-mantenciones',
                'database' => $database,
                'rut_taller' => '76.543.210-2',
                'is_active' => true,
            ],
            [
                'name' => 'Medelauto Servicio Automotriz',
                'slug' => 'medelauto',
                'domain' => 'medelauto',
                'database' => $database,
                'rut_taller' => '76.543.210-3',
                'is_active' => true,
            ],
        ];

        foreach ($talleres as $taller) {
            Tenant::create($taller);
        }
    }
}
