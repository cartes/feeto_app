<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\BoostrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BoostrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_configured_base_url_for_vehicle_lookups(): void
    {
        Config::set('services.boostr.base_url', 'https://custom.boostr.test');
        Config::set('services.boostr.key', 'boostr-token');

        Http::fake([
            'https://custom.boostr.test/vehiculo/AA1111' => Http::response([
                'marca' => 'Toyota',
                'modelo' => 'Yaris',
                'anio' => 2024,
                'vin' => 'VIN123',
                'propietario' => [
                    'nombre' => 'Pedro Cliente',
                    'rut' => '11111111-1',
                ],
            ]),
        ]);

        $vehicleData = app(BoostrService::class)->getVehicleData('AA1111');

        $this->assertSame('Toyota', $vehicleData['marca']);
        $this->assertSame('Yaris', $vehicleData['modelo']);
        $this->assertSame('Pedro Cliente', $vehicleData['nombre_dueno']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://custom.boostr.test/vehiculo/AA1111'
                && $request->hasHeader('Authorization', 'Bearer boostr-token');
        });
    }
}
