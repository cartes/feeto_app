<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\PatentReaderAgent;
use App\Enums\Country;
use App\Http\Requests\PreviewReceptionRequest;
use App\Http\Requests\SearchReceptionClientsRequest;
use App\Http\Requests\StoreReceptionOrderRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\BoostrService;
use App\Services\VehicleCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image as InterventionImage;
use Laravel\Ai\Files\Image;

class ReceptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(VehicleCatalogService $vehicleCatalogService): Response
    {
        $tenant = Tenant::current();
        $maxImageUploadKb = (int) config('reception.image_upload_max_kb', 5120);

        return inertia('Reception/Create', [
            'tenantId' => $tenant?->id,
            'planType' => $tenant?->currentPlan()->value ?? 'gratuito',
            'maxImageUploadBytes' => $maxImageUploadKb * 1024,
            'vehicleCatalogBrands' => $vehicleCatalogService->brandOptions(),
        ]);
    }

    /**
     * Procesamiento OCR y obtención de datos automáticos.
     */
    public function store(Request $request, PatentReaderAgent $agent): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:'.config('reception.image_upload_max_kb', 5120)],
        ]);

        $uploadedImage = $request->file('image');

        try {
            $image = InterventionImage::decode($uploadedImage->getRealPath());

            if ($image->width() > 1280) {
                $image->scaleDown(width: 1280);
            }

            $imagePath = 'reception/temp/'.Str::uuid().'.jpg';
            Storage::disk('public')->put($imagePath, $image->encode(new JpegEncoder(quality: 80))->toString());
        } catch (\Throwable $e) {
            Log::warning('Reception OCR image resize failed, falling back to raw upload: '.$e->getMessage());
            $imagePath = $uploadedImage->store('reception/temp', 'public');
        }

        $provider = (string) config('ai.default_for_images', config('ai.default', 'gemini'));
        $tenant = Tenant::current();

        Log::info('Reception OCR request received.', [
            'tenant_id' => $tenant?->id,
            'user_id' => $request->user()?->id,
            'provider' => $provider,
            'image_path' => $imagePath,
            'image_size' => $uploadedImage->getSize(),
            'image_mime' => $uploadedImage->getMimeType(),
        ]);

        try {
            $response = $agent->prompt(
                'Extrae la patente chilena',
                attachments: [Image::fromPath(storage_path('app/public/'.$imagePath))],
                provider: $provider,
                timeout: (int) config('reception.ai_timeout_seconds', 20),
            );

            $patenteLimpia = $this->normalizePlate((string) ($response['patente'] ?? ''));
            $patenteLimpia = str_replace(['O', 'I'], ['0', '1'], $patenteLimpia);

            if (! preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$|^[A-Z]{2}\d{4}$/', $patenteLimpia)) {
                Log::warning('Reception OCR returned an invalid plate.', [
                    'tenant_id' => $tenant?->id,
                    'user_id' => $request->user()?->id,
                    'provider' => $provider,
                    'image_path' => $imagePath,
                    'raw_plate' => $response['patente'] ?? null,
                    'normalized_plate' => $patenteLimpia,
                    'brand' => $response['marca'] ?? null,
                    'model' => $response['modelo'] ?? null,
                ]);

                return response()->json([
                    'valid' => false,
                    'error' => 'FALLÓ ESCANEO',
                ], 422);
            }

            return response()->json([
                'valid' => true,
                'patente' => $patenteLimpia,
                'vehicle' => [
                    'brand' => $response['marca'] ?? 'SIN DATO',
                    'model' => $response['modelo'] ?? 'SIN DATO',
                    'color' => 'SIN DATO',
                    'client' => 'SIN DATO',
                    'rut' => 'SIN DATO',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Reception OCR failed.', [
                'tenant_id' => $tenant?->id,
                'user_id' => $request->user()?->id,
                'provider' => $provider,
                'image_path' => $imagePath,
                'exception_message' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

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
    public function storeOrder(StoreReceptionOrderRequest $request, VehicleCatalogService $vehicleCatalogService): RedirectResponse
    {
        $plate = $this->normalizePlate($request->validated('plate'));
        $vehicleNames = $vehicleCatalogService->resolveVehicleNames(
            $request->integer('vehicle_brand_id') ?: null,
            $request->integer('vehicle_model_id') ?: null,
            $request->validated('brand'),
            $request->validated('model'),
        );
        $vehicle = Vehicle::query()->with('client')->firstOrNew(['plate' => $plate]);
        $vehicleAlreadyExists = $vehicle->exists;

        $vehicle->fill([
            'plate' => $plate,
            'brand' => $vehicleNames['brand'],
            'model' => $vehicleNames['model'],
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

        $workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => 'recepcion',
            'observations' => 'Creada vía Modal de Recepción Digital',
        ]);

        $this->storeChecklist($workOrder, $request->validated('checklist') ?? []);

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
        $plateOrigin = $this->detectForeignPlateOrigin($patente);

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
            'alert' => $this->computeAppointmentAlert($appointment),
        ] : null;

        $vehicle = Vehicle::where('plate', $patente)->with('client')->first();

        if ($vehicle) {
            return response()->json([
                'is_new' => false,
                'vehicle_exists' => true,
                'owner_source' => 'internal',
                'plate_origin' => $plateOrigin,
                'appointment' => $appointmentPayload,
                'vehicle' => [
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'vin' => $vehicle->vin,
                    'plate' => $vehicle->plate,
                ],
                'client' => [
                    'id' => $vehicle->client?->id,
                    'name' => $vehicle->client?->name,
                    'rut' => $vehicle->client?->rut,
                    'email' => $vehicle->client?->email,
                    'phone' => $vehicle->client?->phone,
                ],
            ]);
        }

        // Las patentes extranjeras no existen en el registro local (Boostr),
        // así que se omite la consulta y se pasa directo a ingreso manual.
        $vehicleData = $plateOrigin === null ? $boostr->getVehicleData($patente) : null;

        if (! $vehicleData) {
            return response()->json([
                'is_new' => true,
                'vehicle_exists' => false,
                'not_found' => true,
                'owner_source' => 'manual',
                'plate_origin' => $plateOrigin,
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
            'plate_origin' => $plateOrigin,
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

    /**
     * Detecta si una patente es extranjera respecto al país del taller.
     *
     * Retorna null cuando la patente calza con el formato local (o no calza
     * con ningún país conocido); en caso contrario, retorna los países
     * candidatos para mostrar el aviso "Esta patente pertenece a [PAÍS]".
     *
     * @return array{countries: list<array{code: string, name: string, flag: string}>}|null
     */
    private function detectForeignPlateOrigin(string $plate): ?array
    {
        $tenantCountry = Tenant::current()?->country() ?? Country::Chile;

        if ($tenantCountry->matchesPlate($plate)) {
            return null;
        }

        $candidates = array_values(array_filter(
            Country::detectFromPlate($plate),
            static fn (Country $country): bool => $country !== $tenantCountry,
        ));

        if ($candidates === []) {
            return null;
        }

        return [
            'countries' => array_map(static fn (Country $country): array => [
                'code' => $country->isoCode(),
                'name' => $country->label(),
                'flag' => $country->flag(),
            ], $candidates),
        ];
    }

    /**
     * Guarda el checklist de recepción (inventario de daños, combustible,
     * objetos de valor y firma del cliente) asociado a la OT recién creada.
     *
     * @param  array<string, mixed>  $checklist
     */
    private function storeChecklist(WorkOrder $workOrder, array $checklist): void
    {
        $fuelLevel = $checklist['fuel_level'] ?? null;
        $damages = array_values($checklist['damages'] ?? []);
        $belongings = array_values($checklist['belongings'] ?? []);
        $notes = trim((string) ($checklist['notes'] ?? ''));
        $signature = (string) ($checklist['signature'] ?? '');

        $hasData = $fuelLevel !== null
            || $damages !== []
            || $belongings !== []
            || $notes !== ''
            || $signature !== '';

        if (! $hasData) {
            return;
        }

        $signaturePath = $signature !== '' ? $this->storeSignatureImage($signature) : null;

        $workOrder->checklist()->create([
            'fuel_level' => $fuelLevel,
            'damages' => $damages,
            'belongings' => $belongings,
            'notes' => $notes !== '' ? $notes : null,
            'signature_path' => $signaturePath,
            'signed_by_name' => $signaturePath !== null
                ? ($checklist['signed_by_name'] ?? null)
                : null,
            'signed_at' => $signaturePath !== null ? now() : null,
        ]);
    }

    /**
     * Decodifica la firma (data URL PNG) y la guarda en el disco privado del tenant.
     */
    private function storeSignatureImage(string $dataUrl): ?string
    {
        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($dataUrl, $prefix)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen($prefix)), true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $tenant = Tenant::current();

        if (! $tenant) {
            return null;
        }

        $path = "tenants/{$tenant->id}/work_orders/firmas/".Str::uuid().'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    private function computeAppointmentAlert(Appointment $appointment): ?string
    {
        $now = now();
        $appointmentDate = $appointment->appointment_date;

        if (! $now->isSameDay($appointmentDate)) {
            return 'wrong_day';
        }

        $minutesDiff = $now->diffInMinutes($appointmentDate, false);

        if ($minutesDiff >= -30 && $minutesDiff <= 60) {
            return null;
        }

        return 'same_day';
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
