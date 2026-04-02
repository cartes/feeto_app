<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** @var list<array{name: string, sku: string, description: string, cost_price: int, selling_price: int, physical_stock: int, reserved_stock: int, min_stock: int}> */
    private const PRODUCTS = [
        [
            'name'           => 'Aceite Motor Liqui Moly 10W40',
            'sku'            => 'ACE-LM-10W40',
            'description'    => 'Aceite motor semisintético Liqui Moly 10W40, botella 1L.',
            'cost_price'     => 31500,
            'selling_price'  => 45000,
            'physical_stock' => 50,
            'reserved_stock' => 0,
            'min_stock'      => 10,
        ],
        [
            'name'           => 'Aceite Motor Shell Helix 5W30',
            'sku'            => 'ACE-SH-5W30',
            'description'    => 'Aceite motor sintético Shell Helix Ultra 5W30, botella 1L.',
            'cost_price'     => 24500,
            'selling_price'  => 35000,
            'physical_stock' => 40,
            'reserved_stock' => 0,
            'min_stock'      => 10,
        ],
        [
            'name'           => 'Filtro de Polen Acondicionado',
            'sku'            => 'FIL-POLEN-AC',
            'description'    => 'Filtro de habitáculo con carbón activado, compatible con la mayoría de vehículos.',
            'cost_price'     => 8400,
            'selling_price'  => 12000,
            'physical_stock' => 25,
            'reserved_stock' => 0,
            'min_stock'      => 5,
        ],
        [
            'name'           => 'Filtro de Combustible Diesel',
            'sku'            => 'FIL-COMB-DSL',
            'description'    => 'Filtro de combustible para motores diésel, alta retención de impurezas.',
            'cost_price'     => 12600,
            'selling_price'  => 18000,
            'physical_stock' => 15,
            'reserved_stock' => 0,
            'min_stock'      => 3,
        ],
        [
            'name'           => 'Neumático Goodyear 205/55 R16',
            'sku'            => 'NEU-GY-20555R16',
            'description'    => 'Neumático Goodyear EfficientGrip Performance 205/55 R16 91V.',
            'cost_price'     => 59500,
            'selling_price'  => 85000,
            'physical_stock' => 40,
            'reserved_stock' => 0,
            'min_stock'      => 4,
        ],
        [
            'name'           => 'Pastillas de Freno Delanteras',
            'sku'            => 'FRE-PAS-DEL',
            'description'    => 'Kit pastillas de freno delanteras semimetálicas, par.',
            'cost_price'     => 19600,
            'selling_price'  => 28000,
            'physical_stock' => 30,
            'reserved_stock' => 0,
            'min_stock'      => 5,
        ],
        [
            'name'           => 'Batería 55 Amperes',
            'sku'            => 'BAT-55AH',
            'description'    => 'Batería libre de mantenimiento 55 Ah 12V, apta para vehículos livianos.',
            'cost_price'     => 45500,
            'selling_price'  => 65000,
            'physical_stock' => 10,
            'reserved_stock' => 0,
            'min_stock'      => 2,
        ],
        [
            'name'           => 'Líquido Limpiaparabrisas 1L',
            'sku'            => 'LIM-PARA-1L',
            'description'    => 'Líquido limpiaparabrisas concentrado, botella 1L.',
            'cost_price'     => 2450,
            'selling_price'  => 3500,
            'physical_stock' => 100,
            'reserved_stock' => 0,
            'min_stock'      => 20,
        ],
    ];

    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            foreach (self::PRODUCTS as $productData) {
                Product::create(array_merge($productData, ['tenant_id' => $tenant->id]));
            }
        }

        Tenant::forgetCurrent();
    }
}

