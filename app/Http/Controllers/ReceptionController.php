<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Laravel\Ai\Files\Image;
use App\Ai\Agents\PatentReaderAgent;
use App\Services\BoostrService;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Events\WorkOrderDraftCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReceptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Reception/Create'); // Usa el alias Helper de Inertia
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
        $image = Image::fromPath(storage_path('app/public/' . $imagePath));

        // 2. Procesamiento Asíncrono con el AI SDK
        $agent->queue('Extrae la patente chilena', attachments: [$image])
            ->then(function ($response) use ($boostr) {
                
                // A. Limpiar y Validar la Patente (Regex Chile)
                $patenteSucia = $response['patente'] ?? '';
                $patenteLimpia = strtoupper(str_replace(['O', 'I', '-'], ['0', '1', ''], $patenteSucia));
                
                if (!preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$|^[A-Z]{2}\d{4}$/', $patenteLimpia)) {
                    // Si es inválida, evitamos la creación en DB (opcional disparar evento de falla al frontend)
                    return;
                }

                // B. Consumir API de Boostr para auto-completar datos
                $vehicleData = $boostr->getVehicleData($patenteLimpia); // Trae marca, modelo, RUT, nombre

                if (!$vehicleData) {
                    return; // Aborta si falla API (en entorno dev, podríamos enmascarar)
                }

                // C. Persistencia Multi-Tenant (El Trait TenantAware hace el filtro y asignación por debajo)
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
                        'vin' => $vehicleData['vin'] ?? null
                    ]
                );

                // D. Creación de la OT en estado Recepción (Kanban Index)
                $workOrder = WorkOrder::create([
                    'vehicle_id' => $vehicle->id,
                    'status' => 'recepcion', 
                    'observations' => 'Creada automáticamente vía ALPR'
                ]);

                // E. Emitir evento Websocket para actualizar el Kanban en Vue
                broadcast(new WorkOrderDraftCreated($workOrder->load('vehicle.client')));
            })
            ->catch(function (\Throwable $e) {
                Log::error('Fallo en OCR: ' . $e->getMessage());
            });

        // 3. Respuesta inmediata al Frontend (No bloquea la pantalla)
        return response()->json(['message' => 'Analizando patente...', 'queue' => true]);
    }
}
