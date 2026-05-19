<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'seo' => [
                'title' => Setting::get('seo_home_title', 'TallerFlow · Software para Talleres Mecánicos en Chile'),
                'description' => Setting::get('seo_home_description', 'TallerFlow digitaliza la gestión de tu taller mecánico en Chile. Kanban en vivo, recepción con IA, inventario inteligente y WhatsApp automatizado. Prueba gratis 14 días.'),
                'og_image' => Setting::get('seo_home_og_image', ''),
            ],
        ]);
    }
}
