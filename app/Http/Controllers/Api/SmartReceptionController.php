<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\Agents\FastPlateRecognitionAgent;
use App\Ai\Agents\PatentRecognitionAgent;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartReceptionController extends Controller
{
    public function scanPlate(
        Request $request,
        FastPlateRecognitionAgent $fastPlateAgent,
        PatentRecognitionAgent $patentRecognitionAgent
    ): JsonResponse {
        $request->validate([
            'image' => ['required', 'image', 'max:15360'],
        ]);

        $imageFile = $request->file('image');
        $provider = (string) config('ai.default_for_images', config('ai.default', 'gemini'));

        $aiResult = $fastPlateAgent->prompt(
            'Extrae unicamente la patente chilena visible en esta imagen.',
            provider: $provider,
            attachments: [$imageFile],
        );

        $plate = $this->sanitizePlate($aiResult['plate'] ?? null);
        $appointment = $this->findTodayAppointmentByPlate($plate);
        $vehiclePayload = $this->vehiclePayloadFromAppointment($appointment);
        $detailedAiResult = null;

        if ($plate === '' || ! $this->hasCompleteVehicleIdentity($vehiclePayload)) {
            $detailedAiResult = $patentRecognitionAgent->prompt(
                'Lee la patente de este vehiculo chileno e identifica marca, modelo y color. Devuelve la respuesta estructurada.',
                provider: $provider,
                attachments: [$imageFile],
            );

            $detailedPlate = $this->sanitizePlate($detailedAiResult['plate'] ?? null);

            if ($detailedPlate !== '' && $detailedPlate !== $plate) {
                $plate = $detailedPlate;
                $appointment = $this->findTodayAppointmentByPlate($plate);
                $vehiclePayload = $this->vehiclePayloadFromAppointment($appointment);
            }

            if (! $this->hasCompleteVehicleIdentity($vehiclePayload)) {
                $vehiclePayload = [
                    'brand' => $vehiclePayload['brand'] ?? $this->normalizeVehicleValue($detailedAiResult['brand'] ?? null),
                    'model' => $vehiclePayload['model'] ?? $this->normalizeVehicleValue($detailedAiResult['model'] ?? null),
                    'color' => $vehiclePayload['color'] ?? $this->normalizeVehicleValue($detailedAiResult['color'] ?? null),
                ];
            }
        }

        return response()->json([
            'plate' => $plate,
            'confidence' => $detailedAiResult['confidence'] ?? null,
            'vehicle' => $vehiclePayload,
            'appointment' => $appointment ? [
                'id' => $appointment->id,
                'time' => $appointment->appointment_date->format('H:i'),
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'client' => $appointment->client ? [
                    'name' => $appointment->client->name,
                    'rut' => $appointment->client->rut,
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

    private function findTodayAppointmentByPlate(string $plate): ?Appointment
    {
        if ($plate === '') {
            return null;
        }

        return Appointment::query()
            ->select([
                'id',
                'client_id',
                'vehicle_id',
                'appointment_date',
                'status',
                'notes',
                'plate',
            ])
            ->with([
                'client:id,name,rut,phone',
                'vehicle:id,brand,model,color',
            ])
            ->where('plate', $plate)
            ->whereDate('appointment_date', today())
            ->first();
    }

    /**
     * @return array{brand: ?string, model: ?string, color: ?string}
     */
    private function vehiclePayloadFromAppointment(?Appointment $appointment): array
    {
        return [
            'brand' => $appointment?->vehicle?->brand,
            'model' => $appointment?->vehicle?->model,
            'color' => $appointment?->vehicle?->color,
        ];
    }

    /**
     * @param  array{brand: ?string, model: ?string, color: ?string}  $vehiclePayload
     */
    private function hasCompleteVehicleIdentity(array $vehiclePayload): bool
    {
        return filled($vehiclePayload['brand']) && filled($vehiclePayload['model']);
    }

    private function sanitizePlate(mixed $plate): string
    {
        return substr(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $plate)), 0, 8);
    }

    private function normalizeVehicleValue(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '' || strcasecmp($normalized, 'desconocido') === 0) {
            return null;
        }

        return $normalized;
    }
}
