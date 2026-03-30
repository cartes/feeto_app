<?php

use App\Http\Controllers\OcrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\Api\WorkOrderModalController;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas Públicas — Visibles para cualquier visitante (clientes del taller)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Tracking de Orden de Trabajo (Público)
Route::get('/ot/{uuid}', [\App\Http\Controllers\TrackingController::class, 'show'])->name('tracking.show');

// Landing page pública del taller (para agendamiento de citas)
Route::get('/taller/{domain}', function (string $domain) {
    return Inertia::render('Public/TallerLanding', ['domain' => $domain]);
})->name('taller.landing');

/*
|--------------------------------------------------------------------------
| Rutas Privadas — Administración del taller (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $initialActivities = WorkOrder::query()
            ->with('vehicle')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn($order) => [
                'work_order_id' => $order->id,
                'plate' => $order->vehicle->plate ?? 'N/A',
                'vehicle' => ($order->vehicle->brand ?? '') . ' ' . ($order->vehicle->model ?? ''),
                'new_status' => $order->status,
                'timestamp' => $order->updated_at->toISOString(),
            ]);

        return Inertia::render('Dashboard', [
            'initialActivities' => $initialActivities
        ]);
    })->name('dashboard');

    // Nueva Recepción
    Route::get('/receptions/create', [ReceptionController::class, 'create'])->name('receptions.create');
    Route::post('/receptions', [ReceptionController::class, 'store'])->name('receptions.store');
    Route::post('/receptions/preview', [ReceptionController::class, 'preview'])->name('receptions.preview');
    Route::post('/receptions/store-order', [ReceptionController::class, 'storeOrder'])->name('receptions.store_order');

    // OCR de Patentes (Antiguo - se puede dejar para retrocompatibilidad por ahora)
    Route::post('/ocr/process', [OcrController::class, 'process'])->name('ocr.process');

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

    // Inventory
    Route::resource('inventory', \App\Http\Controllers\InventoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['inventory' => 'product']);

    // Clients
    Route::resource('clients', \App\Http\Controllers\ClientController::class)->only(['index', 'show']);
    // Link de Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Proxy para servir archivos de storage (bypass symlink & auth con aislamiento de tenant)
Route::get('/media/{path}', function ($path) {
    if (!\Spatie\Multitenancy\Models\Tenant::current()) {
        abort(403, 'Tenant no identificado.');
    }

    $tenantId = \Spatie\Multitenancy\Models\Tenant::current()->id;
    $tenantPrefix = "tenants/{$tenantId}/";

    // Seguridad: Bloqueamos el acceso si el path solicitado no pertenece al tenant actual
    if (!str_starts_with($path, $tenantPrefix)) {
        abort(403, 'Acceso denegado a archivos de otros talleres.');
    }

    // Prevención de Directory Traversal
    if (str_contains($path, '..')) {
        abort(400, 'Ruta inválida.');
    }

    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');

require __DIR__ . '/auth.php';
