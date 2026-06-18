<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PublicServicesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Servicios', [
            'canLogin' => Route::has('login'),
            'seo' => $this->resolveMarketingSeo(
                'services',
                'Características y Módulos · TallerFlow — Sistema para Talleres',
                'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.'
            ),
        ]);
    }
}
