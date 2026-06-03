<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\PatentReaderAgent;
use App\Http\Requests\PreviewReceptionRequest;
use App\Http\Requests\SearchReceptionClientsRequest;
use App\Http\Requests\StoreReceptionOrderRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\BoostrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Laravel\Ai\Files\Image;

class ReceptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $tenant = Tenant::current();

        return inertia('Reception/Create', [
            'tenantId' => $tenant?->id,
            'planType' => $tenant?->currentPlan()->value ?? 'gratuito',
        ]);
    }

    /**
     * Procesamiento OCR y obtención de datos automáticos.
     */
    public function store(Request $request, PatentReaderAgent $agent, BoostrService $boostr): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $imagePath = $request->file('image')->store('reception/temp', 'public');
        $provider = (string) config('ai.default_for_images', config('ai.default', 'gemini'));

        try {
            $response = $agent->prompt(
                'Extrae la patente chilena',
                attachments: [Image::fromPath(storage_path('app/public/'.$imagePath))],
                provider: $provider,
            );

            $patenteLimpia = $this->normalizePlate((string) ($response['patente'] ?? ''));
            $patenteLimpia = str_replace(['O', 'I'], ['0', '1'], $patenteLimpia);

            if (! preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$|^[A-Z]{2}\d{4}$/', $patenteLimpia)) {
                return response()->json([
                    'valid' => false,
                    'error' => 'FALLÓ ESCANEO',
                ], 422);
            }

            $vehicleData = $boostr->getVehicleData($patenteLimpia);

            if (! $vehicleData) {
                $vehicleData = [
                    'rut_dueno' => 'PROVISORIO',
                    'nombre_dueno' => 'CLIENTE NUEVO (SIN API)',
                    'marca' => $response['marca'] ?? 'GENÉRICO',
                    'modelo' => $response['modelo'] ?? 'GENÉRICO',
                    'vin' => null,
                ];
            }

            return response()->json([
                'valid' => true,
                'patente' => $patenteLimpia,
                'vehicle' => [
                    'brand' => $vehicleData['marca'] ?? ($response['marca'] ?? 'N/A'),
                    'model' => $vehicleData['modelo'] ?? ($response['modelo'] ?? 'N/A'),
                    'color' => $vehicleData['color'] ?? 'SIN DATO',
                    'client' => $vehicleData['nombre_dueno'] ?? 'SIN DATO',
                    'rut' => $vehicleData['rut_dueno'] ?? 'SIN DATO',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Fallo en OCR: '.$e->getMessage());

            return response()->json([
                'valid' => false,
                'error' => 'ERROR AL ANALIZAR LA IMAGEN.',
            ], 500);
        } finally {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Guarda definitivamente la Orden de Trabajo desde la Previsualización.
     */
    public function storeOrder(StoreReceptionOrderRequest $request): RedirectResponse
    {
        $plate = $this->normalizePlate($request->validated('plate'));
        $vehicle = Vehicle::query()->with('client')->firstOrNew(['plate' => $plate]);
        $vehicleAlreadyExists = $vehicle->exists;

        $vehicle->fill([
            'plate' => $plate,
            'brand' => $request->validated('brand'),
            'model' => $request->validated('model'),
        ]);

        if ($vehicleAlreadyExists && ! $request->boolean('reassign_vehicle_owner')) {
            $currentClient = $vehicle->client;

            if ($currentClient instanceof Client) {
                $this->fillClientContactFromRequest($currentClient, $request);
            } else {
                $vehicle->client()->associate($this->resolveClientFromRequest($request));
            }
        } else {
            $vehicle->client()->associate($this->resolveClientFromRequest($request));
        }

        $vehicle->save();

        WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => 'recepcion',
            'observations' => 'Creada vía Modal de Recepción Digital',
        ]);

        $appointmentId = $request->integer('appointment_id');
        if ($appointmentId > 0) {
            $appointment = Appointment::find($appointmentId);
            if ($appointment && $appointment->status === 'pending') {
                $appointment->update(['status' => 'arrived', 'vehicle_id' => $vehicle->id]);
            }
        }

        return redirect()->route('work-orders.index')->with('success', 'Orden creada exitosamente');
    }

    /**
     * Vista previa de la orden antes de guardar.
     */
    public function preview(PreviewReceptionRequest $request, BoostrService $boostr): JsonResponse
    {
        $patente = $this->normalizePlate($request->validated('patente'));

        $appointment = Appointment::where(function ($q) use ($patente): void {
            $q->where('plate', $patente)
                ->orWhereRaw("REPLACE(REPLACE(plate, '-', ''), '·', '') = ?", [$patente]);
        })->where('status', 'pending')->orderBy('appointment_date')->first();

        $appointmentPayload = $appointment ? [
            'id' => $appointment->id,
            'date' => $appointment->appointment_date,
            'notes' => $appointment->notes,
            'pre_check_notes' => $appointment->pre_check_notes,
            'customer_name' => $appointment->customer_name,
            'phone' => $appointment->phone,
        ] : null;

        $vehicle = Vehicle::where('plate', $patente)->with('client')->first();

        if ($vehicle) {
            return response()->json([
                'is_new' => false,
                'vehicle_exists' => true,
                'owner_source' => 'internal',
                'appointment' => $appointmentPayload,
                'vehicle' => [
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'vin' => $vehicle->vin,
                    'plate' => $vehicle->plate,
                ],
                'client' => [
                    'id' => $vehicle->client?->id,
                    'name' => $vehicle->client->name,
                    'rut' => $vehicle->client->rut,
                    'email' => $vehicle->client->email,
                    'phone' => $vehicle->client->phone,
                ],
            ]);
        }

        $vehicleData = $boostr->getVehicleData($patente);

        if (! $vehicleData) {
            return response()->json([
                'is_new' => true,
                'vehicle_exists' => false,
                'not_found' => true,
                'owner_source' => 'manual',
                'appointment' => $appointmentPayload,
                'vehicle' => [
                    'plate' => $patente,
                    'brand' => '',
                    'model' => '',
                    'vin' => null,
                ],
                'client' => [
                    'id' => null,
                    'name' => $appointment?->customer_name ?? '',
                    'rut' => '',
                    'email' => '',
                    'phone' => $appointment?->phone ?? '',
                ],
            ]);
        }

        return response()->json([
            'is_new' => true,
            'vehicle_exists' => false,
            'owner_source' => 'boostr',
            'appointment' => $appointmentPayload,
            'vehicle' => [
                'brand' => $vehicleData['marca'] ?? 'N/A',
                'model' => $vehicleData['modelo'] ?? 'N/A',
                'vin' => $vehicleData['vin'] ?? 'N/A',
                'plate' => $patente,
            ],
            'client' => [
                'id' => null,
                'name' => $vehicleData['nombre_dueno'] ?? 'SIN DATO',
                'rut' => $vehicleData['rut_dueno'] ?? 'SIN DATO',
                'email' => '',
                'phone' => '',
            ],
        ]);
    }

    public function searchClients(SearchReceptionClientsRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $escapedSearch = addcslashes($search, '%_');

        $clients = Client::query()
            ->where(function ($query) use ($escapedSearch): void {
                $query
                    ->where('name', 'like', "%{$escapedSearch}%")
                    ->orWhere('rut', 'like', "%{$escapedSearch}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'rut', 'email', 'phone']);

        return response()->json([
            'clients' => $clients,
        ]);
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $plate));
    }

    private function resolveClientFromRequest(StoreReceptionOrderRequest $request): Client
    {
        $selectedClientId = $request->integer('selected_client_id');

        if ($selectedClientId > 0) {
            $client = Client::query()->findOrFail($selectedClientId);

            return $this->fillClientFromRequest($client, $request);
        }

        $client = Client::query()->firstOrNew([
            'rut' => $request->validated('client_rut'),
        ]);

        return $this->fillClientFromRequest($client, $request);
    }

    private function fillClientFromRequest(Client $client, StoreReceptionOrderRequest $request): Client
    {
        $client->fill(array_filter([
            'name' => $request->validated('client_name'),
            'rut' => $request->validated('client_rut'),
            'email' => $request->validated('client_email'),
            'phone' => $request->validated('client_phone'),
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));

        $client->save();

        return $client;
    }

    private function fillClientContactFromRequest(Client $client, StoreReceptionOrderRequest $request): Client
    {
        $client->fill(array_filter([
            'email' => $request->validated('client_email'),
            'phone' => $request->validated('client_phone'),
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));

        $client->save();

        return $client;
    }
}
