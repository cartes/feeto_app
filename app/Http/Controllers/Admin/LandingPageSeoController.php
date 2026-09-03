<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLandingPageSeoRequest;
use App\Http\Requests\Admin\UpdateMarketingWhatsAppRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\MarketingWhatsAppService;
use App\Support\MarketingSeoPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class LandingPageSeoController extends Controller
{
    public function index(MarketingWhatsAppService $marketingWhatsAppService): Response
    {
        $pages = collect(MarketingSeoPages::all())->map(function (array $page, string $key): array {
            return [
                'key' => $key,
                'label' => $page['label'],
                'url' => route($page['route'], [], false),
                'og_type' => $page['og_type'],
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
        }

        Setting::set('analytics_google_analytics_code', $validated['analytics_google_analytics_code'] ?? null);
        Setting::set('analytics_google_search_console_code', $validated['analytics_google_search_console_code'] ?? null);

        AuditLog::record('analytics_settings.updated', 'Super-admin actualizó la configuración SEO y analytics del sitio público');

        return back()->with('success', 'Configuración SEO y analytics guardada correctamente.');
    }

    public function updateWhatsApp(UpdateMarketingWhatsAppRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('marketing_whatsapp_enabled', $validated['marketing_whatsapp_enabled']);
        Setting::set('marketing_whatsapp_number', $validated['marketing_whatsapp_number'] ?? null);
        Setting::set('marketing_whatsapp_message', $validated['marketing_whatsapp_message'] ?? null);

        AuditLog::record('analytics_settings.updated', 'Super-admin actualizó el botón flotante de WhatsApp del sitio público');

        return back()->with('success', 'Configuración de WhatsApp guardada correctamente.');
    }

    public function uploadOgImage(Request $request, string $pageKey): RedirectResponse
    {
        if (! MarketingSeoPages::has($pageKey)) {
            abort(404);
        }

        $request->validate([
            'og_image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $uploaded = $request->file('og_image');
        $directory = "seo/{$pageKey}";
        $filename = 'og-image.webp';
        $path = "{$directory}/{$filename}";

        try {
            $image = Image::decode($uploaded->getRealPath());

            if ($image->width() > 1200) {
                $image->scaleDown(width: 1200);
            }

            Storage::disk('public')->put($path, $image->encode(new WebpEncoder(quality: 85))->toString());
        } catch (\Throwable $e) {
            Log::warning("OG image upload processing failed for page {$pageKey}: ".$e->getMessage());

            $extension = $uploaded->getClientOriginalExtension() ?: 'jpg';
            $filename = 'og-image.'.$extension;
            $path = "{$directory}/{$filename}";
            Storage::disk('public')->putFileAs($directory, $uploaded, $filename);
        }

        $this->deleteOgImageFiles($pageKey, except: $path);

        Setting::set("seo_{$pageKey}_og_image", Storage::disk('public')->url($path));

        AuditLog::record('analytics_settings.updated', "Super-admin actualizó la imagen OG de la página '{$pageKey}'");

        return back()->with('success', 'Imagen OG actualizada correctamente.');
    }

    public function deleteOgImage(string $pageKey): RedirectResponse
    {
        if (! MarketingSeoPages::has($pageKey)) {
            abort(404);
        }

        $this->deleteOgImageFiles($pageKey);
        Setting::set("seo_{$pageKey}_og_image", '');

        AuditLog::record('analytics_settings.updated', "Super-admin eliminó la imagen OG de la página '{$pageKey}'");

        return back()->with('success', 'Imagen OG eliminada correctamente.');
    }

    private function deleteOgImageFiles(string $pageKey, ?string $except = null): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $existing = "seo/{$pageKey}/og-image.{$extension}";

            if ($existing !== $except && Storage::disk('public')->exists($existing)) {
                Storage::disk('public')->delete($existing);
            }
        }
    }
}
