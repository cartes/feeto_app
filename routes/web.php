<?php

use App\Http\Controllers\OcrController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/receptions/create', [\App\Http\Controllers\ReceptionController::class, 'create'])->name('receptions.create');
    Route::post('/receptions', [\App\Http\Controllers\ReceptionController::class, 'store'])->name('receptions.store');

    // OCR de Patentes (Antiguo - se puede dejar para retrocompatibilidad por ahora)
    Route::post('/ocr/process', [OcrController::class, 'process'])->name('ocr.process');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
