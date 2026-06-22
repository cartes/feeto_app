<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /** @var list<string> */
    private const CATEGORIES = [
        'Aceites y Lubricantes',
        'Filtros',
        'Frenos',
        'Suspensión y Dirección',
        'Motor',
        'Transmisión y Embrague',
        'Sistema Eléctrico',
        'Refrigeración y Calefacción',
        'Escape y Emisiones',
        'Neumáticos y Llantas',
        'Carrocería y Accesorios',
        'Iluminación',
        'Correas y Mangueras',
        'Baterías',
        'Líquidos y Químicos',
        'Herramientas e Insumos',
    ];

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

    private function seedForTenant(Tenant $tenant): void
    {
        foreach (self::CATEGORIES as $name) {
            ProductCategory::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => Str::slug($name),
                ],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                ],
            );
        }
    }
}
