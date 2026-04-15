<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\PatentReaderAgent;
use App\Events\PatentRecognized;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\BoostrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response;
use Laravel\Ai\Files\Image;
use Spatie\Multitenancy\Models\Tenant;

class ReceptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Reception/Create', [
            'tenantId' => app(Tenant::class)->current() ? app(Tenant::class)->current()->id : null,
            'planType' => app(Tenant::class)->current() ? app(Tenant::class)->current()->plan_type : 'freemium',
        ]);
    }

    /**
     * Procesamiento asíncrono de OCR y obtención de datos automáticos.
     */
    public function store(Request $request, PatentReaderAgent $agent, BoostrService $boostr)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        // 1. Recibir imagen nativa de la cámara
        $imagePath = $request->file('image')->store('reception/temp', 'public');
        $image = Image::fromPath(storage_path('app/public/'.$imagePath));

        // 2. Procesamiento Asíncrono con el AI SDK
        $agent->queue('Extrae la patente chilena', attachments: [$image])
            ->then(function ($response) use ($boostr) {

                // A. Limpiar y Validar la Patente (Regex Chile)
                $patenteSucia = $response['patente'] ?? '';
                // Quitamos todo lo que no sea letra o número
                $patenteLimpia = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $patenteSucia));
                // Heurística para errores comunes de OCR en Chile
                $patenteLimpia = str_replace(['O', 'I'], ['0', '1'], $patenteLimpia);

                if (! preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$|^[A-Z]{2}\d{4}$/', $patenteLimpia)) {
                    broadcast(new PatentRecognized('ERROR_FORMATO', ''));

                    return;
                }

                // B. Obtención de datos del vehículo (Priorizamos la IA sobre el mockup si falla Boostr)
                $vehicleData = $boostr->getVehicleData($patenteLimpia);

                if (! $vehicleData) {
                    // Si Boostr falla o no hay API key, usamos lo que detectó la IA el paso anterior
                    $vehicleData = [
                        'rut_dueno' => 'PROVISORIO',
                        'nombre_dueno' => 'CLIENTE NUEVO (SIN API)',
                        'marca' => $response['marca'] ?? 'GENÉRICO',
                        'modelo' => $response['modelo'] ?? 'GENÉRICO',
                        'vin' => null,
                    ];
                }

                // C. Persistencia de datos del vehículo si no existe (No creamos la OT aún)
                $client = Client::firstOrCreate(
                    ['rut' => $vehicleData['rut_dueno']],
                    ['name' => $vehicleData['nombre_dueno']]
                );

                $vehicle = Vehicle::firstOrCreate(
                    ['plate' => $patenteLimpia],
                    [
                        'client_id' => $client->id,
                        'brand' => $vehicleData['marca'],
                        'model' => $vehicleData['modelo'],
                        'vin' => $vehicleData['vin'] ?? null,
                    ]
                );

                // D. Emitir el evento para que el frontend del que escanea no se quede pensando
                broadcast(new PatentRecognized($patenteLimpia, '', [
                    'brand' => $vehicleData['marca'] ?? 'N/A',
                    'model' => $vehicleData['modelo'] ?? 'N/A',
                    'color' => $vehicleData['color'] ?? 'SIN DATO',
                    'client' => $vehicleData['nombre_dueno'] ?? 'SIN DATO',
                    'rut' => $vehicleData['rut_dueno'] ?? 'SIN DATO',
                ]));
            })
            ->catch(function (\Throwable $e) {
                Log::error('Fallo en OCR: '.$e->getMessage());
                broadcast(new PatentRecognized('ERROR_FORMATO', ''));
            });

        // 3. Respuesta inmediata al Frontend (No bloquea la pantalla)
        return response()->json(['message' => 'Analizando patente...', 'queue' => true]);
    }

    /**
     * Guarda definitivamente la Orden de Trabajo desde la Previsualización.
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'plate' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'client_name' => 'required|string',
            'client_rut' => 'required|string',
            'client_email' => 'nullable|email',
            'client_phone' => 'nullable|string',
        ]);

        // Aseguramos que el cliente exista/se actualice
        $client = Client::updateOrCreate(
            ['rut' => $request->client_rut],
            array_filter([
                'name' => $request->client_name,
                'email' => $request->client_email,
                'phone' => $request->client_phone,
            ])
        );

        // Aseguramos que el vehículo exista/se actualice
        $vehicle = Vehicle::updateOrCreate(
            ['plate' => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $request->plate))],
            [
                'client_id' => $client->id,
                'brand' => $request->brand,
                'model' => $request->model,
            ]
        );

        // Creamos la OT iniciada en estado borrador (recepcion)
        $workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => 'recepcion',
            'observations' => 'Creada vía Modal de Recepción Digital',
        ]);

        return redirect()->route('work-orders.index')->with('success', 'Orden creada exitosamente');
    }

    /**
     * Vista previa de la orden antes de guardar.
     */
    public function preview(Request $request, BoostrService $boostr)
    {
        $request->validate(['patente' => 'required|string']);

        // Limpiamos la patente por si acaso
        $patenteRaw = $request->patente;
        $patente = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $patenteRaw));

        // 1. Buscamos si existe en la base de datos (aislado por tenant automáticamente)
        $vehicle = Vehicle::where('plate', $patente)->with('client')->first();

        if ($vehicle) {
            return response()->json([
                'is_new' => false,
                'vehicle' => [
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'vin' => $vehicle->vin,
                    'plate' => $vehicle->plate,
                ],
                'client' => [
                    'name' => $vehicle->client->name,
                    'rut' => $vehicle->client->rut,
                ],
            ]);
        }

        // 2. Si es nuevo, consultamos a Boostr (API externa)
        $vehicleData = $boostr->getVehicleData($patente);

        if (! $vehicleData) {
            // Si Boostr también falla, devolvemos un objeto vacío para que el frontend pida llenado manual
            return response()->json([
                'is_new' => true,
                'not_found' => true,
                'vehicle' => [
                    'plate' => $patente,
                    'brand' => 'NO IDENTIFICADO',
                    'model' => 'NO IDENTIFICADO',
                    'vin' => 'N/A',
                ],
                'client' => [
                    'name' => 'CLIENTE NUEVO',
                    'rut' => '',
                ],
            ]);
        }

        return response()->json([
            'is_new' => true,
            'vehicle' => [
                'brand' => $vehicleData['marca'] ?? 'N/A',
                'model' => $vehicleData['modelo'] ?? 'N/A',
                'vin' => $vehicleData['vin'] ?? 'N/A',
                'plate' => $patente,
            ],
            'client' => [
                'name' => $vehicleData['nombre_dueno'] ?? 'SIN DATO',
                'rut' => $vehicleData['rut_dueno'] ?? 'SIN DATO',
            ],
        ]);
    }
}
