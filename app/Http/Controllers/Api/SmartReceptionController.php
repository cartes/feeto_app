<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\Agents\PatentRecognitionAgent;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartReceptionController extends Controller
{
    public function scanPlate(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:15360'],
        ]);

        $imageFile = $request->file('image');

        // Prompt Gemini with the uploaded image directly from PHP's temp buffer.
        // The file is NEVER written to a permanent disk (cumplimiento Ley 19.628).
        $aiResult = (new PatentRecognitionAgent)->prompt(
            'Lee la patente de este vehículo chileno. Devuelve ÚNICAMENTE los 6 caracteres alfanuméricos, sin guiones, sin espacios, sin texto adicional.',
            provider: 'gemini',
            attachments: [$imageFile],
        );

        // Sanitize: strip anything that isn't A-Z or 0-9, cap at 8 chars
        $rawPlate = strtoupper((string) ($aiResult['plate'] ?? ''));
        $plate    = substr(preg_replace('/[^A-Z0-9]/', '', $rawPlate), 0, 8);

        // Cross-check with today's agenda
        $appointment = null;

        if ($plate !== '') {
            $appointment = Appointment::with(['client', 'vehicle'])
                ->where('plate', $plate)
                ->whereDate('appointment_date', today())
                ->first();
        }

        /*
        |----------------------------------------------------------------------
        | Boostr API – fetch owner data for vehicles not yet in the system
        |----------------------------------------------------------------------
        | Uncomment to enable external plate lookup for new clients.
        |
        | $ownerData = null;
        | if (! $appointment && $plate !== '') {
        |     $res = \Illuminate\Support\Facades\Http::withHeaders([
        |         'Authorization' => 'Bearer ' . config('services.boostr.token'),
        |     ])->get("https://api.boostr.cl/patentes/{$plate}");
        |
        |     if ($res->ok()) {
        |         $ownerData = $res->json();
        |         // Returns: name, rut, brand, model, year, color, fuel_type
        |     }
        | }
        */

        return response()->json([
            'plate'      => $plate,
            'confidence' => $aiResult['confidence'] ?? null,
            'vehicle'    => [
                'brand' => $aiResult['brand'] ?? null,
                'model' => $aiResult['model'] ?? null,
                'color' => $aiResult['color'] ?? null,
            ],
            'appointment' => $appointment ? [
                'id'     => $appointment->id,
                'time'   => $appointment->appointment_date->format('H:i'),
                'status' => $appointment->status,
                'notes'  => $appointment->notes,
                'client' => $appointment->client ? [
                    'name'  => $appointment->client->name,
                    'rut'   => $appointment->client->rut,
                    'phone' => $appointment->client->phone,
                ] : null,
                'vehicle' => $appointment->vehicle ? [
                    'brand' => $appointment->vehicle->brand,
                    'model' => $appointment->vehicle->model,
                    'color' => $appointment->vehicle->color,
                ] : null,
            ] : null,
        ]);
    }
}
