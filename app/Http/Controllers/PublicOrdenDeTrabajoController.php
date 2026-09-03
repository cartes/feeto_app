<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PublicOrdenDeTrabajoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Blog/OrdenDeTrabajo', [
            'canLogin' => Route::has('login'),
            'seo' => $this->resolveMarketingSeo('orden_trabajo'),
        ]);
    }
}
