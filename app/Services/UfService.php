<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UfService
{
    private const CACHE_KEY = 'uf_clp_valor';

    private const CACHE_TTL_SECONDS = 86400;

    private const API_URL = 'https://mindicador.cl/api/uf';

    /**
     * Retorna el valor de la UF en CLP del día, cacheado 24 horas.
     * Retorna null si la API no está disponible (el frontend lo oculta con v-if).
     */
    public function getCurrentValue(): ?float
    {
        if (Cache::has(self::CACHE_KEY)) {
            return (float) Cache::get(self::CACHE_KEY);
        }

        $valor = $this->fetchFromApi();

        if ($valor !== null) {
            Cache::put(self::CACHE_KEY, $valor, self::CACHE_TTL_SECONDS);
        }

        return $valor;
    }

    private function fetchFromApi(): ?float
    {
        try {
            $response = Http::timeout(5)->get(self::API_URL);

            if (! $response->successful()) {
                Log::warning('UfService: mindicador.cl respondió con status '.$response->status());

                return null;
            }

            $valor = $response->json('serie.0.valor');

            if (! is_numeric($valor) || (float) $valor <= 0) {
                Log::warning('UfService: valor UF inesperado en respuesta de mindicador.cl', ['valor' => $valor]);

                return null;
            }

            return (float) $valor;

        } catch (\Throwable $e) {
            Log::error('UfService: fallo al obtener UF desde mindicador.cl: '.$e->getMessage());

            return null;
        }
    }
}
