<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLandingPageSeoRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\MarketingWhatsAppService;
use Illuminate\Http\RedirectResponse;
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

    public function index(MarketingWhatsAppService $marketingWhatsAppService): Response
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

        $analyticsSettings = Setting::getGroup('analytics')->keyBy('key')->map(fn ($s) => [
            'value' => $s->value,
            'description' => $s->description,
            'is_secret' => $s->is_secret,
            'has_value' => ! empty($s->value),
        ]);

        return Inertia::render('Admin/LandingPageSeo/Index', [
            'pages' => $pages,
            'analytics_settings' => $analyticsSettings,
            'marketing_whatsapp' => $marketingWhatsAppService->settings(),
        ]);
    }

    public function update(UpdateLandingPageSeoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated['pages'] as $page) {
            $key = $page['key'];
            Setting::set("seo_{$key}_title", $page['title'] ?? '');
            Setting::set("seo_{$key}_description", $page['description'] ?? '');
            Setting::set("seo_{$key}_og_image", $page['og_image'] ?? '');
        }

        Setting::set('analytics_google_analytics_code', $validated['analytics_google_analytics_code'] ?? null);
        Setting::set('analytics_google_search_console_code', $validated['analytics_google_search_console_code'] ?? null);
        Setting::set('marketing_whatsapp_enabled', $validated['marketing_whatsapp_enabled']);
        Setting::set('marketing_whatsapp_number', $validated['marketing_whatsapp_number'] ?? null);
        Setting::set('marketing_whatsapp_message', $validated['marketing_whatsapp_message'] ?? null);

        AuditLog::record('analytics_settings.updated', 'Super-admin actualizó la configuración SEO, analytics y WhatsApp del sitio público');

        return back()->with('success', 'Configuración SEO, analytics y WhatsApp guardada correctamente.');
    }
}
