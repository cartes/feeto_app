<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Database\Seeders\VehicleCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_keeps_distinct_codes_and_collapses_true_duplicates(): void
    {
        $seeder = new class extends VehicleCatalogSeeder
        {
            protected function rawCatalog(): array
            {
                return [
                    [
                        'name' => 'BYD',
                        'models' => ['ATTO 3', 'ATTO3', 'DOLPHIN', 'CX 5', 'CX-5'],
                    ],
                ];
            }
        };

        $seeder->run();

        $brand = VehicleBrand::query()->where('name', 'BYD')->firstOrFail();
        $models = VehicleModel::query()
            ->where('vehicle_brand_id', $brand->id)
            ->orderBy('name')
            ->get(['name', 'code']);

        $this->assertCount(4, $models);
        $this->assertSame([
            ['name' => 'ATTO 3', 'code' => 'ATTO-3'],
            ['name' => 'ATTO3', 'code' => 'ATTO3'],
            ['name' => 'CX 5', 'code' => 'CX-5'],
            ['name' => 'DOLPHIN', 'code' => 'DOLPHIN'],
        ], $models->map(fn (VehicleModel $model): array => $model->only(['name', 'code']))->all());
    }
}
