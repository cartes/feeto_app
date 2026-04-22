<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MercadoPagoWebhookController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SmartReceptionController;
use App\Http\Controllers\Api\WorkOrderItemController;
use App\Http\Controllers\Api\WorkOrderModalController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TallerDashboardController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\SetTenantRouteDefaults;
use App\Models\Tenant;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Rutas Públicas — Visibles para cualquier visitante (clientes del taller)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// Tracking de Orden de Trabajo (Público con rate limit)
Route::get('/ot/{uuid}', [TrackingController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('tracking.show');
Route::post('/ot/{uuid}/quote/respond', [QuoteController::class, 'respond'])
    ->middleware('throttle:20,1')
    ->name('tracking.quote.respond');

// Landing page pública del taller — embudo de conversión con Pre-Check ALPR
Route::get('/taller/{tenantBySlug}', [PublicBookingController::class, 'show'])->name('taller.landing');
Route::post('/taller/{tenantBySlug}/booking', [PublicBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('taller.booking.store');

/*
|--------------------------------------------------------------------------
| Rutas Privadas — Administración del taller (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function (Request $request): RedirectResponse {
    $user = $request->user();

    if ($user->is_super_admin) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->tenant) {
        return redirect()->route('taller.dashboard', ['tenantBySlug' => $user->tenant->slug]);
    }

    abort(403, 'Este usuario no tiene un taller asignado.');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', NeedsTenant::class, SetTenantRouteDefaults::class])
    ->prefix('/taller/{tenantBySlug}')
    ->group(function () {
        Route::get('/dashboard', TallerDashboardController::class)->name('taller.dashboard');

        // Nueva Recepción — Admin y Recepcionista
        Route::get('/receptions/create', [ReceptionController::class, 'create'])
            ->middleware('role:Admin|Recepcionista')
            ->name('receptions.create');
        Route::post('/receptions', [ReceptionController::class, 'store'])
            ->middleware(['throttle:20,1', 'role:Admin|Recepcionista', 'tenant.feature:ai_reception'])
            ->name('receptions.store');
        Route::post('/receptions/preview', [ReceptionController::class, 'preview'])
            ->middleware(['throttle:30,1', 'role:Admin|Recepcionista'])
            ->name('receptions.preview');
        Route::post('/receptions/store-order', [ReceptionController::class, 'storeOrder'])
            ->middleware('role:Admin|Recepcionista')
            ->name('receptions.store_order');

        // OCR de Patentes
        Route::post('/ocr/process', [OcrController::class, 'process'])
            ->middleware(['throttle:20,1', 'role:Admin|Recepcionista', 'tenant.feature:ai_reception'])
            ->name('ocr.process');

        // Work Orders / Kanban — Admin, Recepcionista y Mecanico (con restricciones granulares en controlador)
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::put('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
            ->middleware('role:Admin|Recepcionista|Mecanico')
            ->name('work-orders.status.update');
        Route::post('/work-orders/{workOrder}/items', [WorkOrderController::class, 'addItem'])
            ->middleware('role:Admin|Mecanico')
            ->name('work-orders.items.store');
        Route::delete('/work-orders/{workOrder}/items/{item}', [WorkOrderController::class, 'removeItem'])
            ->middleware('role:Admin|Mecanico')
            ->name('work-orders.items.destroy');
        Route::post('/work-orders/{workOrder}/quote/send', [QuoteController::class, 'send'])
            ->middleware('role:Admin|Recepcionista|Mecanico')
            ->name('work-orders.quote.send');

        // API Modals
        Route::get('/api/work-orders/{id}', [WorkOrderModalController::class, 'show'])->name('api.work-orders.show');
        Route::post('/api/work-orders/{id}/images', [WorkOrderModalController::class, 'uploadImage'])->name('api.work-orders.images.upload');
        Route::delete('/api/work-orders/images/{imageId}', [WorkOrderModalController::class, 'destroyImage'])->name('api.work-orders.images.destroy');

        Route::post('/api/work-orders/{workOrder}/items', [WorkOrderItemController::class, 'store'])
            ->middleware('role:Admin|Mecanico')
            ->name('api.work-orders.items.store');
        Route::delete('/api/work-orders/{workOrder}/items/{item}', [WorkOrderItemController::class, 'destroy'])
            ->middleware('role:Admin|Mecanico')
            ->name('api.work-orders.items.destroy');

        Route::get('/api/products', [ProductController::class, 'index'])->name('api.products.index');

        // Inventory — solo Admin
        Route::resource('inventory', InventoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['inventory' => 'product'])
            ->middleware('role:Admin');

        Route::resource('services', ServiceController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['services' => 'service'])
            ->middleware('role:Admin');

        // Branches — solo Admin
        Route::resource('branches', BranchController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['branches' => 'branch'])
            ->middleware('role:Admin');

        // Clients — Admin y Recepcionista
        Route::resource('clients', ClientController::class)
            ->only(['index', 'show'])
            ->middleware('role:Admin|Recepcionista');

        // Appointments & Smart Reception — Admin y Recepcionista
        Route::get('/appointments', [AppointmentController::class, 'index'])
            ->middleware('role:Admin|Recepcionista')
            ->name('appointments.index');
        Route::post('/api/appointments/scan-plate', [SmartReceptionController::class, 'scanPlate'])
            ->middleware(['role:Admin|Recepcionista', 'tenant.feature:ai_reception'])
            ->name('api.appointments.scan-plate');

        // Gestión de usuarios del taller — solo Admin
        Route::get('/users', [TenantUserController::class, 'index'])
            ->middleware('role:Admin')
            ->name('tenant.users.index');
        Route::post('/users', [TenantUserController::class, 'store'])
            ->middleware('role:Admin')
            ->name('tenant.users.store');
        Route::delete('/users/{user}', [TenantUserController::class, 'destroy'])
            ->middleware('role:Admin')
            ->name('tenant.users.destroy');

        // Configuración del taller — solo Admin
        Route::get('/settings', [TenantSettingsController::class, 'index'])
            ->middleware('role:Admin')
            ->name('taller.settings');

        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('role:Admin')
            ->name('reports.index');

        // Perfil
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/media/{path}', function (string $path) {
            $user = auth()->user();
            $tenant = Tenant::current();

            if (! $tenant || $user->tenant_id !== $tenant->id) {
                abort(403, 'No tienes acceso a estos archivos.');
            }

            $tenantPrefix = "tenants/{$tenant->id}/";

            if (! str_starts_with($path, $tenantPrefix)) {
                abort(403, 'Acceso denegado a archivos de otros talleres.');
            }

            if (str_contains($path, '..')) {
                abort(400, 'Ruta inválida.');
            }

            $fullPath = storage_path('app/public/'.$path);

            if (! file_exists($fullPath)) {
                abort(404);
            }

            return response()->file($fullPath);
        })->where('path', '.*')->name('storage.serve');
    });

// Admin Route Group without NeedsTenant middleware
Route::middleware(['auth', 'verified', IsSuperAdmin::class])
    ->withoutMiddleware([NeedsTenant::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        // Perfil & API Keys
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('/profile/api-keys', [AdminProfileController::class, 'updateApiKeys'])->name('profile.api-keys');

        // Planes
        Route::resource('/plans', PlanController::class)->except(['show']);

        // Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::put('/tenants/{tenant}/admin', [TenantController::class, 'updateAdmin'])->name('tenants.update_admin');
        Route::put('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');

        // Usuarios
        Route::get('/users', [UserController::class, 'index'])->name('users.index');

        // Audit Log
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        // Pagos
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/preference', [PaymentController::class, 'createPreference'])->name('payments.preference');
        Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
        Route::get('/payments/failure', [PaymentController::class, 'failure'])->name('payments.failure');
        Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    });

// Mercado Pago webhook (sin CSRF, sin auth — validado por firma HMAC)
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('admin.payments.webhook')
    ->withoutMiddleware([PreventRequestForgery::class]);

require __DIR__.'/auth.php';

// *** RUTA TEMPORAL DE DIAGNÓSTICO — eliminar en producción ***
if (config('app.env') === 'local') {
    Route::get('/debug/roles', function () {
        $results = [];

        $tenants = Tenant::with('users.roles')->get();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $tenantData = [
                'tenant' => $tenant->name,
                'slug' => $tenant->slug,
                'roles_en_bd' => Role::pluck('name'),
                'users' => [],
            ];

            foreach ($tenant->users as $user) {
                $tenantData['users'][] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ];
            }

            $results[] = $tenantData;
            Tenant::forgetCurrent();
        }

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    })->name('debug.roles');

    Route::get('/debug/fix-roles', function () {
        $tenants = Tenant::with('users.roles')->get();
        $fixed = [];

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Crear roles si no existen
            app(TenantSetupService::class)->provisionTenant($tenant);

            foreach ($tenant->users as $user) {
                $user->refresh();
                if ($user->roles->isEmpty()) {
                    $user->assignRole('Admin');
                    $user->refresh();
                    $fixed[] = "Asignado Admin a {$user->name} en {$tenant->name}";
                }
            }

            Tenant::forgetCurrent();
        }

        return response()->json([
            'mensaje' => 'Fix completado',
            'acciones' => $fixed ?: ['Sin cambios necesarios'],
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.fix-roles');
}
