<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BoostrService
{
    protected ?string $apiKey = null;
    protected ?string $baseUrl = null;

    public function __construct()
    {
        $this->apiKey = config('services.boostr.key');
        $this->baseUrl = config('services.boostr.base_url') ?? 'https://api.boostr.cl';
    }

    /**
     * Fetch vehicle information by license plate.
     *
     * @param string $plate Cleaned license plate (PPU).
     * @return array{name: string|null, rut: string|null, brand: string|null, model: string|null, vin: string|null}
     */
    public function getVehicleData(string $plate): array
    {
        if (empty($this->apiKey)) {
            Log::warning('Boostr API Key is not set.');
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/v1/vehiculos/{$plate}");

            if ($response->failed()) {
                Log::error("Boostr API error for plate {$plate}: " . $response->body());
                return $this->emptyResponse();
            }

            $data = $response->json();

            return [
                'name' => $data['propietario']['nombre'] ?? $data['nombre'] ?? null,
                'rut' => $data['propietario']['rut'] ?? $data['rut'] ?? null,
                'brand' => $data['marca'] ?? null,
                'model' => $data['modelo'] ?? null,
                'vin' => $data['vin'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("Exception calling Boostr API for plate {$plate}: " . $e->getMessage());
            return $this->emptyResponse();
        }
    }

    /**
     * Standardized empty response.
     */
    protected function emptyResponse(): array
    {
        return [
            'name' => null,
            'rut' => null,
            'brand' => null,
            'model' => null,
            'vin' => null,
        ];
    }
}
