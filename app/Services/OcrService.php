<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\PatentRecognitionAgent;
use App\Enums\Country;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Image;

class OcrService
{
    public function __construct(
        protected PatentRecognitionAgent $agent
    ) {}

    public function processImage(string $imagePath): array
    {
        try {
            $absolutePath = Storage::disk('public')->path($imagePath);
            $provider = (string) config('ai.default_for_images', config('ai.default', 'gemini'));

            $response = $this->agent->prompt(
                'Identifica la patente y los datos del vehículo en esta imagen.',
                attachments: [Image::fromPath($absolutePath)],
                provider: $provider,
            );

            $rawPlate = $response['plate'] ?? null;

            if (! $rawPlate) {
                return $this->failureResponse($imagePath);
            }

            $cleanedPlate = $this->cleanPlate($rawPlate);
            $validation = $this->validatePpu($cleanedPlate);

            // Corrección típica de OCR (O→0, I→1) solo si la lectura directa
            // no calza: O e I son letras válidas en patentes extranjeras.
            if (! $validation['valid']) {
                $corrected = $this->applyOcrCorrections($cleanedPlate);
                $correctedValidation = $this->validatePpu($corrected);

                if ($correctedValidation['valid']) {
                    $cleanedPlate = $corrected;
                    $validation = $correctedValidation;
                }
            }

            $this->cleanup($imagePath);

            return [
                'plate' => $validation['valid'] ? $validation['plate'] : $cleanedPlate,
                'valid' => $validation['valid'],
                'type' => $validation['type'],
                'brand' => $response['brand'] ?? 'Desconocido',
                'model' => $response['model'] ?? 'Desconocido',
                'color' => $response['color'] ?? 'Desconocido',
                'confidence' => (float) ($response['confidence'] ?? 0),
            ];
        } catch (\Throwable $e) {
            Log::error('OCR error: '.$e->getMessage());
            $this->cleanup($imagePath);

            return [
                'error' => 'Error: '.$e->getMessage(),
                'valid' => false,
            ];
        }
    }

    protected function cleanup(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function failureResponse(string $path): array
    {
        $this->cleanup($path);

        return [
            'error' => 'No se detectó el vehículo.',
            'valid' => false,
        ];
    }

    public function cleanPlate(string $plate): string
    {
        return (string) preg_replace('/[^A-Z0-9]/i', '', strtoupper($plate));
    }

    public function applyOcrCorrections(string $plate): string
    {
        return str_replace(['O', 'I'], ['0', '1'], $plate);
    }

    public function validatePpu(string $ppu): array
    {
        if (preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$/', $ppu)) {
            return ['valid' => true, 'type' => 'moderna', 'plate' => $ppu];
        }
        if (preg_match('/^[A-Z]{2}\d{4}$/', $ppu)) {
            return ['valid' => true, 'type' => 'antigua', 'plate' => $ppu];
        }
        if (preg_match('/^[BCDFGHJKLPRSTVWXYZ]{3}\d{2}$/', $ppu)) {
            return ['valid' => true, 'type' => 'moto_moderna', 'plate' => $ppu];
        }
        if (preg_match('/^[A-Z]{2}\d{3}$/', $ppu)) {
            return ['valid' => true, 'type' => 'moto_antigua', 'plate' => $ppu];
        }

        // Patentes internacionales (otros países latinoamericanos).
        if (Country::detectFromPlate($ppu) !== []) {
            return ['valid' => true, 'type' => 'internacional', 'plate' => $ppu];
        }

        return ['valid' => false, 'type' => null, 'plate' => $ppu];
    }
}
