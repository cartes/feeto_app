<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageSeoController extends Controller
{
    private const PAGES = [
        'home' => [
            'label' => 'Página de Inicio',
            'url' => '/',
            'default_title' => 'TallerFlow · Software para Talleres Mecánicos en Chile',
            'default_description' => 'TallerFlow digitaliza la gestión de tu taller mecánico en Chile. Kanban en vivo, recepción con IA, inventario inteligente y WhatsApp automatizado. Prueba gratis 14 días.',
        ],
        'pricing' => [
            'label' => 'Página de Precios',
            'url' => '/precios',
            'default_title' => 'Planes y Precios · TallerFlow — Software para Talleres',
            'default_description' => 'Elige el plan TallerFlow ideal para tu taller mecánico. Desde $19.990/mes con 14 días de prueba gratis. Sin tarjeta de crédito requerida.',
        ],
        'trial' => [
            'label' => 'Solicitar Prueba Gratuita',
            'url' => '/trial',
            'default_title' => 'Prueba Gratis 14 días · TallerFlow — Software para Talleres',
            'default_description' => 'Solicita tu acceso gratuito de 14 días a TallerFlow. Sin tarjeta de crédito. Sin compromisos. Activa tu taller digital hoy.',
        ],
    ];

    public function index(): Response
    {
        $pages = collect(self::PAGES)->map(function (array $page, string $key): array {
            return [
                'key' => $key,
                'label' => $page['label'],
                'url' => $page['url'],
                'title' => Setting::get("seo_{$key}_title", $page['default_title']),
                'description' => Setting::get("seo_{$key}_description", $page['default_description']),
                'og_image' => Setting::get("seo_{$key}_og_image", ''),
                'default_title' => $page['default_title'],
                'default_description' => $page['default_description'],
            ];
        })->values();

        return Inertia::render('Admin/LandingPageSeo/Index', [
            'pages' => $pages,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*.key' => ['required', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'pages.*.title' => 'nullable|string|max:160',
            'pages.*.description' => 'nullable|string|max:320',
            'pages.*.og_image' => 'nullable|url|max:500',
        ], [
            'pages.*.title.max' => 'El título SEO no debe superar los 160 caracteres.',
            'pages.*.description.max' => 'La descripción SEO no debe superar los 320 caracteres.',
            'pages.*.og_image.url' => 'La imagen OG debe ser una URL válida.',
        ]);

        foreach ($validated['pages'] as $page) {
            $key = $page['key'];
            Setting::set("seo_{$key}_title", $page['title'] ?? '');
            Setting::set("seo_{$key}_description", $page['description'] ?? '');
            Setting::set("seo_{$key}_og_image", $page['og_image'] ?? '');
        }

        return back()->with('success', 'Configuración SEO guardada correctamente.');
    }
}
