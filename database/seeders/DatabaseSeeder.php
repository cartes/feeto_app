<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear un Tenant para localhost
        Tenant::create([
            'name' => 'Taller Local',
            'domain' => 'localhost',
            'rut_taller' => '11.111.111-1',
            'is_active' => true,
        ]);

        // Crear un Tenant de prueba (necesario para el multi-tenancy)
        Tenant::create([
            'name' => 'Taller de Prueba',
            'domain' => 'prueba.feeto.test',
            'rut_taller' => '12.345.678-9',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario Admin',
            'email' => 'admin@feeto.cl',
            'password' => 'password', // El cast 'hashed' se encarga de esto
        ]);
    }
}
