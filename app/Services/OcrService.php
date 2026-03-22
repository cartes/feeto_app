<?php
declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\PatentRecognitionAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Image;

class OcrService
{
    public function __construct(
        protected PatentRecognitionAgent $agent
    ) {
    }

    public function processImage(string $imagePath): array
    {
        try {
            $absolutePath = Storage::disk('public')->path($imagePath);

            $response = $this->agent->prompt(
                'Identifica la patente y los datos del vehículo en esta imagen.',
                attachments: [Image::fromPath($absolutePath)]
            );

            $rawPlate = $response['plate'] ?? null;
            
            if (!$rawPlate) {
                return $this->failureResponse($imagePath);
            }

            $cleanedPlate = $this->cleanPlate($rawPlate);
            $validation = $this->validatePpu($cleanedPlate);

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
            Log::error("OCR error: " . $e->getMessage());
            $this->cleanup($imagePath);
            return [
                'error' => 'Error: ' . $e->getMessage(),
                'valid' => false
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
            'valid' => false
        ];
    }

    public function cleanPlate(string $plate): string
    {
        $clean = preg_replace('/[^A-Z0-9]/i', '', strtoupper($plate));
        return str_replace(['O', 'I'], ['0', '1'], $clean);
    }

    public function validatePpu(string $ppu): array
    {
        if (preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$/', $ppu)) {
            return ['valid' => true, 'type' => 'moderna', 'plate' => $ppu];
        }
        if (preg_match('/^[A-Z]{2}\d{4}$/', $ppu)) {
            return ['valid' => true, 'type' => 'antigua', 'plate' => $ppu];
        }
        return ['valid' => false, 'type' => null, 'plate' => $ppu];
    }
}
