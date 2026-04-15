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
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\TallerDashboardController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\SetTenantRouteDefaults;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

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

        // Nueva Recepción
        Route::get('/receptions/create', [ReceptionController::class, 'create'])->name('receptions.create');
        Route::post('/receptions', [ReceptionController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('receptions.store');
        Route::post('/receptions/preview', [ReceptionController::class, 'preview'])
            ->middleware('throttle:30,1')
            ->name('receptions.preview');
        Route::post('/receptions/store-order', [ReceptionController::class, 'storeOrder'])->name('receptions.store_order');

        // OCR de Patentes (Antiguo - se puede dejar para retrocompatibilidad por ahora)
        Route::post('/ocr/process', [OcrController::class, 'process'])
            ->middleware('throttle:20,1')
            ->name('ocr.process');

        // Work Orders / Kanban
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::put('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.status.update');
        Route::post('/work-orders/{workOrder}/items', [WorkOrderController::class, 'addItem'])->name('work-orders.items.store');
        Route::delete('/work-orders/{workOrder}/items/{item}', [WorkOrderController::class, 'removeItem'])->name('work-orders.items.destroy');

        // API Modals
        Route::get('/api/work-orders/{id}', [WorkOrderModalController::class, 'show'])->name('api.work-orders.show');
        Route::post('/api/work-orders/{id}/images', [WorkOrderModalController::class, 'uploadImage'])->name('api.work-orders.images.upload');
        Route::delete('/api/work-orders/images/{imageId}', [WorkOrderModalController::class, 'destroyImage'])->name('api.work-orders.images.destroy');

        Route::post('/api/work-orders/{workOrder}/items', [WorkOrderItemController::class, 'store'])->name('api.work-orders.items.store');
        Route::delete('/api/work-orders/{workOrder}/items/{item}', [WorkOrderItemController::class, 'destroy'])->name('api.work-orders.items.destroy');

        Route::get('/api/products', [ProductController::class, 'index'])->name('api.products.index');

        // Inventory
        Route::resource('inventory', InventoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['inventory' => 'product']);

        // Clients
        Route::resource('clients', ClientController::class)->only(['index', 'show']);

        // Appointments & Smart Reception
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('/api/appointments/scan-plate', [SmartReceptionController::class, 'scanPlate'])->name('api.appointments.scan-plate');

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
