<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BoostrService;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function __construct(
        protected OcrService $ocrService,
        protected BoostrService $boostrService
    ) {
    }

    public function process(Request $request): JsonResponse
    {
        if (!$request->hasFile('image')) {
            return response()->json([
                'message' => 'No se recibió la imagen en la petición.',
                'errors' => ['image' => ['No file uploaded.']]
            ], 422);
        }

        $file = $request->file('image');

        if (!$file->isValid()) {
            return response()->json([
                'message' => 'Error de subida PHP: ' . $file->getErrorMessage(),
                'errors' => ['image' => [$file->getErrorMessage()]]
            ], 422);
        }

        $request->validate([
            'image' => 'required|image|max:15360', // Subido a 15MB
        ]);

        $path = $file->store('ocr', 'public');

        $ocrResult = $this->ocrService->processImage($path);

        if ($ocrResult['valid'] ?? false) {
            $vehicleData = $this->boostrService->getVehicleData((string) $ocrResult['plate']);
            
            // Only merge non-null values to avoid overwriting AI detected data with empty results
            $ocrResult = array_merge($ocrResult, array_filter($vehicleData, fn($val) => !is_null($val)));
        }

        return response()->json($ocrResult);
    }
}
