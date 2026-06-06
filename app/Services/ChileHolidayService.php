<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChileHolidayService
{
    private const API_URL = 'https://apis.digital.gob.cl/fl/feriados';

    /** @return list<array{date: string, label: string, type: string, irrenunciable: bool}> */
    public function forYear(int $year): array
    {
        $cacheKey = "chile_holidays_{$year}";
        $ttl = now()->year < $year ? now()->addDay() : now()->addDays(7);

        return Cache::remember($cacheKey, $ttl, function () use ($year): array {
            try {
                $response = Http::timeout(10)->get(self::API_URL.'/'.$year);

                if ($response->failed()) {
                    return [];
                }

                return collect($response->json())
                    ->filter(fn (mixed $h): bool => is_array($h) && isset($h['fecha'], $h['nombre']))
                    ->map(fn (array $h): array => [
                        'date' => $h['fecha'],
                        'label' => $h['nombre'],
                        'type' => 'official',
                        'irrenunciable' => (bool) ($h['irrenunciable'] ?? false),
                    ])
                    ->sortBy('date')
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('ChileHolidayService: failed to fetch holidays', [
                    'year' => $year,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }
}
