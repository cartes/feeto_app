<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            ProductSeeder::class,
        ]);

        User::factory()->create([
            'name'     => 'Usuario Admin',
            'email'    => 'admin@feeto.cl',
            'password' => 'password',
        ]);
    }
}
