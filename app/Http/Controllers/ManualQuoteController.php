<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddQuoteItemRequest;
use App\Http\Requests\RespondToQuoteRequest;
use App\Http\Requests\SendQuoteRequest;
use App\Http\Requests\StoreManualQuoteRequest;
use App\Http\Requests\UpdateQuoteTaxRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ManualQuoteService;
use App\Services\PlanFeatureService;
use App\Services\UfService;
use App\Services\VehicleCatalogService;
use App\Services\WorkOrderQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ManualQuoteController extends Controller
{
    public function __construct(
        protected ManualQuoteService $manualQuoteService,
        protected WorkOrderQuoteService $workOrderQuoteService,
        protected PlanFeatureService $planFeatureService,
        protected UfService $ufService,
        protected VehicleCatalogService $vehicleCatalogService,
    ) {}

    public function index(Request $request): Response
    {
        $this->ensureCommercialQuotesEnabled();

        $search = $request->input('search');

        $quotes = Quote::query()
            ->whereNotNull('client_id')
            ->with(['client:id,name,rut', 'vehicle:id,plate,brand,model', 'workOrder:id,uuid,status'])
            ->when($search, function ($query, $search): void {
                $escaped = addcslashes($search, '%_');
                $query->whereHas('client', function ($clientQuery) use ($escaped): void {
                    $clientQuery->where('name', 'like', "%{$escaped}%")
                        ->orWhere('rut', 'like', "%{$escaped}%");
                })->orWhereHas('vehicle', function ($vehicleQuery) use ($escaped): void {
                    $vehicleQuery->where('plate', 'like', "%{$escaped}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => ['search' => $search],
            'quoteStatuses' => Quote::statuses(),
        ]);
    }

    public function create(): Response
    {
        $this->ensureCommercialQuotesEnabled();

        return Inertia::render('Quotes/Create', [
            'vehicleCatalogBrands' => $this->vehicleCatalogService->brandOptions(),
        ]);
    }

    public function store(StoreManualQuoteRequest $request): RedirectResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $quote = $this->manualQuoteService->createDraft($request->validated());

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Cotización creada. Ahora puedes agregar ítems.');
    }

    public function show(Quote $quote): Response
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();

        $quote->load([
            'client:id,name,rut,phone,email',
            'vehicle:id,plate,brand,model',
            'items.product',
            'items.service',
            'events',
            'workOrder:id,uuid,status',
        ]);

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'selling_price', 'physical_stock'])
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->select(['id', 'name', 'code', 'selling_price', 'estimated_minutes'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
            'taxName' => $quote->taxName(),
            'defaultTaxRate' => Tenant::current()?->defaultTaxRate() ?? 19.0,
            'products' => $products,
            'services' => $services,
            'uf_value' => $this->ufService->getCurrentValue(),
            'discountPolicy' => [
                'threshold' => Tenant::current()?->maxDiscountWithoutApproval() ?? 10,
                'approver_roles' => ['Jefe', 'Supervisor'],
            ],
            'canDeliverQuote' => $this->canDeliverQuote(request()->user()),
        ]);
    }

    public function addItem(AddQuoteItemRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();

        $validated = $request->validated();
        $itemPayload = $this->workOrderQuoteService->buildItemPayload($validated);
        $this->ensureDiscountIsAllowed($request, $itemPayload['discount_percent']);

        $this->manualQuoteService->addItem($quote, $validated);

        return back();
    }

    public function removeItem(Quote $quote, QuoteItem $item): RedirectResponse
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();

        $this->manualQuoteService->removeItem($quote, $item);

        return back();
    }

    public function updateTax(UpdateQuoteTaxRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();

        $validated = $request->validated();
        $applyTax = (bool) $validated['apply_tax'];
        $taxRate = isset($validated['tax_rate']) && $validated['tax_rate'] !== null && $validated['tax_rate'] !== ''
            ? (float) $validated['tax_rate']
            : null;

        $this->manualQuoteService->updateTax($quote, $applyTax, $taxRate);

        return back()->with('success', 'Configuración de impuestos actualizada.');
    }

    public function send(SendQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();
        $this->ensureUserCanDeliverQuote($request->user());

        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return back()->withErrors([
                'quote' => 'La cotización ya fue aceptada por el cliente.',
            ]);
        }

        if (! $quote->items()->exists()) {
            return back()->withErrors([
                'quote' => 'Debes agregar al menos un ítem antes de enviar la cotización.',
            ]);
        }

        $channel = $request->validated('channel') ?? 'manual';

        $this->manualQuoteService->send($quote, $channel);

        return back()->with('success', $this->sendSuccessMessage($channel));
    }

    public function approveManually(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorizeQuoteAccess($quote);
        $this->ensureCommercialQuotesEnabled();
        $this->ensureUserCanDeliverQuote($request->user());

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:phone,whatsapp,email,other'],
        ]);

        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return back()->withErrors(['quote' => 'La cotización ya fue aceptada.']);
        }

        if (! $quote->items()->exists()) {
            return back()->withErrors([
                'quote' => 'Debes agregar al menos un ítem antes de aprobar la cotización.',
            ]);
        }

        $workOrder = $this->manualQuoteService->approveManually($quote, $validated['channel']);

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Cotización aprobada. Se generó la Orden de Trabajo #'.$workOrder->id.'.');
    }

    public function respond(RespondToQuoteRequest $request, string $uuid): RedirectResponse
    {
        $quote = Quote::withoutGlobalScope('tenant')
            ->where('uuid', $uuid)
            ->whereNotNull('client_id')
            ->firstOrFail();

        $tenant = Tenant::query()->find($quote->tenant_id);
        $this->ensureCommercialQuotesEnabled($tenant);

        if ($quote->status !== Quote::STATUS_PENDING_CUSTOMER) {
            abort(409, 'La cotización no está disponible para respuesta.');
        }

        $decision = $request->validated('decision');

        $this->manualQuoteService->respondPublicly($quote, $decision, $request->validated('notes'));

        return back()->with('success', $decision === 'accepted'
            ? 'Cotización aceptada.'
            : 'Cotización rechazada.');
    }

    public function searchByPlate(Request $request): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $raw = $request->input('plate', '');
        $plate = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $raw));

        if (strlen($plate) < 2) {
            return response()->json(['results' => []]);
        }

        $escaped = addcslashes($plate, '%_');

        $vehicles = Vehicle::query()
            ->where('plate', 'like', "%{$escaped}%")
            ->with('client:id,name,rut,phone,email')
            ->limit(8)
            ->get(['id', 'client_id', 'plate', 'brand', 'model']);

        return response()->json(['results' => $vehicles]);
    }

    public function storeClientWithVehicle(Request $request): JsonResponse
    {
        $this->ensureCommercialQuotesEnabled();

        $validated = $request->validate([
            'plate' => ['required', 'string', 'max:10'],
            'vehicle_brand_id' => ['nullable', 'integer', Rule::exists('vehicle_brands', 'id')],
            'vehicle_model_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicle_models', 'id')->where(static function ($query) use ($request): void {
                    $brandId = $request->integer('vehicle_brand_id');

                    if ($brandId > 0) {
                        $query->where('vehicle_brand_id', $brandId);
                    }
                }),
            ],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'rut' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'secondary_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
        $vehicleNames = $this->vehicleCatalogService->resolveVehicleNames(
            $request->integer('vehicle_brand_id') ?: null,
            $request->integer('vehicle_model_id') ?: null,
            $validated['vehicle_brand'] ?? null,
            $validated['vehicle_model'] ?? null,
        );

        $normalizedPlate = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $validated['plate']));

        $tenantId = Tenant::current()?->id;

        $existingVehicle = Vehicle::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('plate', $normalizedPlate)
            ->first();

        if ($existingVehicle) {
            return response()->json([
                'errors' => ['plate' => ['Ya existe un vehículo con esta patente en el sistema.']],
            ], 422);
        }

        $client = Client::firstOrCreate(
            ['rut' => $validated['rut']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'secondary_phone' => $validated['secondary_phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]
        );

        $vehicle = $client->vehicles()->create([
            'plate' => $normalizedPlate,
            'brand' => $vehicleNames['brand'] !== '' ? $vehicleNames['brand'] : null,
            'model' => $vehicleNames['model'] !== '' ? $vehicleNames['model'] : null,
        ]);

        $vehicle->load('client:id,name,rut,phone,email');

        return response()->json([
            'client' => $client->only(['id', 'name', 'rut', 'phone', 'email']),
            'vehicle' => $vehicle->only(['id', 'plate', 'brand', 'model', 'client_id']),
        ]);
    }

    /**
     * Devuelve los vehículos de un cliente para alimentar el selector dependiente.
     */
    public function vehiclesForClient(Client $client): JsonResponse
    {
        if ($client->tenant_id !== Tenant::current()?->id) {
            abort(403);
        }

        $vehicles = $client->vehicles()
            ->select(['id', 'plate', 'brand', 'model'])
            ->orderBy('plate')
            ->get();

        return response()->json(['vehicles' => $vehicles]);
    }

    private function authorizeQuoteAccess(Quote $quote): void
    {
        $user = request()->user();

        if ($user->is_super_admin) {
            return;
        }

        if ($user->tenant_id !== $quote->tenant_id) {
            abort(403, 'No tienes permiso para gestionar esta cotización.');
        }

        if (! $quote->isManual()) {
            abort(404);
        }
    }

    private function ensureCommercialQuotesEnabled(?Tenant $tenant = null): void
    {
        $tenant ??= Tenant::current();

        if (! $tenant?->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES)) {
            abort(403, $this->planFeatureService->upgradeMessage(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES));
        }
    }

    private function ensureDiscountIsAllowed(Request $request, float $discountPercent): void
    {
        $tenant = Tenant::current();

        if (! $tenant || $discountPercent <= $tenant->maxDiscountWithoutApproval()) {
            return;
        }

        $user = $request->user();

        if ($user->is_super_admin || $user->hasAnyRole(['Jefe', 'Supervisor'])) {
            return;
        }

        throw ValidationException::withMessages([
            'discount_percent' => sprintf(
                'Los descuentos mayores a %s%% requieren rol Jefe o Supervisor.',
                rtrim(rtrim(number_format($tenant->maxDiscountWithoutApproval(), 2, '.', ''), '0'), '.')
            ),
        ]);
    }

    private function ensureUserCanDeliverQuote(User $user): void
    {
        if (! $this->canDeliverQuote($user)) {
            abort(403, 'Solo Admin, Supervisor o Jefe pueden enviar la cotización al cliente.');
        }
    }

    private function canDeliverQuote(User $user): bool
    {
        return $user->is_super_admin
            || $user->hasAnyRole(['Admin', 'Supervisor', 'Jefe']);
    }

    private function sendSuccessMessage(string $channel): string
    {
        return match ($channel) {
            'whatsapp' => 'Cotización enviada al cliente. Puedes continuar el contacto por WhatsApp.',
            'email' => 'Cotización enviada al cliente. Puedes continuar el contacto por correo.',
            'both' => 'Cotización enviada al cliente. Puedes continuar el contacto por WhatsApp y correo.',
            default => 'Cotización enviada al cliente.',
        };
    }
}
