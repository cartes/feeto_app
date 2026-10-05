<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Services\VehicleCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_options_are_sorted_and_contain_only_ids_and_names(): void
    {
        $toyota = $this->createBrand('Toyota');
        $mazda = $this->createBrand('Mazda');

        $this->assertSame([
            ['id' => $mazda->id, 'name' => 'Mazda'],
            ['id' => $toyota->id, 'name' => 'Toyota'],
        ], app(VehicleCatalogService::class)->brandOptions());
    }

    public function test_empty_catalogs_return_empty_options(): void
    {
        $service = app(VehicleCatalogService::class);

        $this->assertSame([], $service->brandOptions());
        $this->assertSame([], $service->modelOptionsForBrand($this->createBrand('Toyota')));
    }

    public function test_model_options_are_sorted_and_isolated_by_brand(): void
    {
        $toyota = $this->createBrand('Toyota');
        $mazda = $this->createBrand('Mazda');
        $yaris = $this->createModel($toyota, 'Yaris');
        $corolla = $this->createModel($toyota, 'Corolla');
        $cx5 = $this->createModel($mazda, 'CX5');
        $service = app(VehicleCatalogService::class);

        $this->assertSame([
            ['id' => $corolla->id, 'name' => 'Corolla'],
            ['id' => $yaris->id, 'name' => 'Yaris'],
        ], $service->modelOptionsForBrand($toyota));
        $this->assertSame([
            ['id' => $cx5->id, 'name' => 'CX5'],
        ], $service->modelOptionsForBrand($mazda));
    }

    public function test_brand_options_are_cached_for_one_hour(): void
    {
        $this->freezeTime();
        $brand = $this->createBrand('Toyota');
        $service = app(VehicleCatalogService::class);
        $originalOptions = $service->brandOptions();
        $brand->update(['name' => 'Toyota updated']);

        $this->travel(3599)->seconds();
        $this->assertSame($originalOptions, $service->brandOptions());

        $this->travel(2)->seconds();
        $this->assertSame([
            ['id' => $brand->id, 'name' => 'Toyota updated'],
        ], $service->brandOptions());
    }

    public function test_model_options_are_cached_for_one_hour(): void
    {
        $this->freezeTime();
        $brand = $this->createBrand('Toyota');
        $model = $this->createModel($brand, 'Corolla');
        $service = app(VehicleCatalogService::class);
        $originalOptions = $service->modelOptionsForBrand($brand);
        $model->update(['name' => 'Corolla updated']);

        $this->travel(3599)->seconds();
        $this->assertSame($originalOptions, $service->modelOptionsForBrand($brand));

        $this->travel(2)->seconds();
        $this->assertSame([
            ['id' => $model->id, 'name' => 'Corolla updated'],
        ], $service->modelOptionsForBrand($brand));
    }

    public function test_catalog_names_take_precedence_over_supplied_names(): void
    {
        $brand = $this->createBrand('Toyota');
        $model = $this->createModel($brand, 'Corolla');

        $this->assertSame([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ], app(VehicleCatalogService::class)->resolveVehicleNames($brand->id, $model->id, 'Other brand', 'Other model'));
    }

    public function test_missing_ids_use_trimmed_names_and_null_names_become_empty_strings(): void
    {
        $service = app(VehicleCatalogService::class);

        $this->assertSame([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ], $service->resolveVehicleNames(null, null, ' Toyota ', ' Corolla '));
        $this->assertSame([
            'brand' => '',
            'model' => '',
        ], $service->resolveVehicleNames(null, null, null, null));
    }

    public function test_nonexistent_ids_use_supplied_names(): void
    {
        $this->assertSame([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ], app(VehicleCatalogService::class)->resolveVehicleNames(999, 999, ' Toyota ', ' Corolla '));
    }

    public function test_model_from_another_brand_uses_supplied_model_name(): void
    {
        $toyota = $this->createBrand('Toyota');
        $mazda = $this->createBrand('Mazda');
        $model = $this->createModel($mazda, 'CX5');

        $this->assertSame([
            'brand' => 'Toyota',
            'model' => 'Custom model',
        ], app(VehicleCatalogService::class)->resolveVehicleNames($toyota->id, $model->id, null, ' Custom model '));
    }

    public function test_model_can_be_resolved_without_a_brand_id(): void
    {
        $brand = $this->createBrand('Toyota');
        $model = $this->createModel($brand, 'Corolla');

        $this->assertSame([
            'brand' => 'Custom brand',
            'model' => 'Corolla',
        ], app(VehicleCatalogService::class)->resolveVehicleNames(null, $model->id, ' Custom brand ', null));
    }

    private function createBrand(string $name): VehicleBrand
    {
        return VehicleBrand::query()->create(['name' => $name, 'code' => strtoupper($name)]);
    }

    private function createModel(VehicleBrand $brand, string $name): VehicleModel
    {
        return $brand->models()->create(['name' => $name, 'code' => strtoupper($name)]);
    }
}
