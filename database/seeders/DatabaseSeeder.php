<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            PlanSeeder::class,
            TenantSeeder::class,
            ProductSeeder::class,
        ]);

        // Super Admin (sin tenant)
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@feeto.cl',
            'password' => bcrypt('password'),
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        // Usuario de prueba por cada tenant
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            User::factory()->create([
                'name' => 'Admin '.$tenant->name,
                'email' => 'admin@'.$tenant->domain.'.test',
                'password' => bcrypt('password'),
                'is_super_admin' => false,
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
