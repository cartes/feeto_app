<?php

use App\Http\Controllers\OcrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\WorkOrderController;
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
        return Inertia::render('Dashboard');
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
    Route::put('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.status.update');

    // Inventory
    Route::resource('inventory', \App\Http\Controllers\InventoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['inventory' => 'product']);

    // Clients
    Route::resource('clients', \App\Http\Controllers\ClientController::class)->only(['index', 'show']);
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
