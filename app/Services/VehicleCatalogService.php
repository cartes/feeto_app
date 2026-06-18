<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\Cache;

class VehicleCatalogService
{
    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function brandOptions(): array
    {
        return Cache::remember('vehicle-catalog.brands', 3600, static function (): array {
            return VehicleBrand::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (VehicleBrand $brand): array => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ])
                ->all();
        });
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function modelOptionsForBrand(VehicleBrand $vehicleBrand): array
    {
        return Cache::remember("vehicle-catalog.brand.{$vehicleBrand->id}.models", 3600, static function () use ($vehicleBrand): array {
            return $vehicleBrand->models()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (VehicleModel $model): array => [
                    'id' => $model->id,
                    'name' => $model->name,
                ])
                ->all();
        });
    }

    /**
     * @return array{brand: string, model: string}
     */
    public function resolveVehicleNames(?int $brandId, ?int $modelId, ?string $brandName, ?string $modelName): array
    {
        $brand = $brandId !== null
            ? VehicleBrand::query()->find($brandId)
            : null;

        $model = $modelId !== null
            ? VehicleModel::query()
                ->when($brandId !== null, static fn ($query) => $query->where('vehicle_brand_id', $brandId))
                ->find($modelId)
            : null;

        return [
            'brand' => $brand?->name ?? trim((string) $brandName),
            'model' => $model?->name ?? trim((string) $modelName),
        ];
    }
}
