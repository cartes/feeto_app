<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;

class VehicleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = $this->catalog();
        $timestamp = now();

        DB::transaction(function () use ($catalog, $timestamp): void {
            VehicleModel::query()->delete();
            VehicleBrand::query()->delete();

            VehicleBrand::query()->insert(array_map(static fn (array $brand): array => [
                'name' => $brand['name'],
                'code' => $brand['code'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $catalog));

            $brandsByCode = VehicleBrand::query()
                ->get(['id', 'code'])
                ->keyBy('code');

            $modelRows = [];

            foreach ($catalog as $brand) {
                $brandId = $brandsByCode[$brand['code']]->id;

                foreach ($brand['models'] as $model) {
                    $modelRows[] = [
                        'vehicle_brand_id' => $brandId,
                        'name' => $model['name'],
                        'code' => $model['code'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }

            foreach (array_chunk($modelRows, 1000) as $chunk) {
                VehicleModel::query()->insert($chunk);
            }
        });
    }

    /**
     * @return array<int, array{name: string, code: string, models: array<int, array{name: string, code: string}>}>
     */
    private function catalog(): array
    {
        try {
            /** @var array<int, array{name: string, models: array<int, string>}> $catalog */
            $catalog = json_decode(
                (string) file_get_contents(database_path('data/vehicle_catalog_chile.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new \RuntimeException('No se pudo leer el catálogo global de vehículos.', previous: $exception);
        }

        return array_map(function (array $brand): array {
            return [
                'name' => $brand['name'],
                'code' => $this->normalizeCode($brand['name']),
                'models' => array_map(fn (string $model): array => [
                    'name' => $model,
                    'code' => $this->normalizeCode($model),
                ], $brand['models']),
            ];
        }, $catalog);
    }

    private function normalizeCode(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '', $normalized));

        return $normalized !== '' ? $normalized : 'CATALOG';
    }
}
