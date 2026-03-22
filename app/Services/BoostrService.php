<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BoostrService
{
    /**
     * Obtiene los datos de la patente desde la API de Boostr.
     *
     * @param  string  $patente  La patente a consultar
     * @return array<string, mixed>|null Objeto con los datos o null en caso de error
     */
    public function getVehicleData(string $patente): ?array
    {
        try {
            // Se asume que la URL base o endpoint y API Key están en config/services.php
            $apiUrl = config('services.boostr.url', 'https://api.boostr.cl/vehiculo/'.$patente);
            $apiKey = config('services.boostr.key');

            $response = Http::withToken($apiKey ?? '')
                ->timeout(10)
                ->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();

                // Extrayendo la información premium del vehículo
                return [
                    'marca' => $data['marca'] ?? 'Desconocida',
                    'modelo' => $data['modelo'] ?? 'Desconocido',
                    'anio' => $data['anio'] ?? 'Desconocido',
                    'vin' => $data['vin'] ?? 'No disponible',
                    'nombre_dueno' => $data['propietario']['nombre'] ?? 'Sin asignar',
                    'rut_dueno' => $data['propietario']['rut'] ?? 'No disponible',
                ];
            }

            Log::warning('Boostr API returned status: '.$response->status()." for patente: {$patente}");

            return null;

        } catch (\Throwable $e) {
            Log::error('Error communicating with Boostr API: '.$e->getMessage());

            return null;
        }
    }
}
