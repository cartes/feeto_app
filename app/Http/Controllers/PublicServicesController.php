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
            'seo' => $this->resolveMarketingSeo('services'),
        ]);
    }
}
