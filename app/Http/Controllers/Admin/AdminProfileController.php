<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use App\Http\Requests\Admin\UpdateAnalyticsRequest;
use App\Http\Requests\Admin\UpdateApiKeysRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    public function edit(): Response
    {
        $aiSettings = $this->buildSettingsPayload('ai', [
            'ai_provider' => [
                'config' => 'ai.default',
                'default' => 'gemini',
                'description' => 'Proveedor IA activo para texto',
                'is_secret' => false,
            ],
            'ai_image_provider' => [
                'config' => 'ai.default_for_images',
                'default' => 'gemini',
                'description' => 'Proveedor IA activo para imágenes (OCR)',
                'is_secret' => false,
            ],
            'gemini_api_key' => [
                'config' => 'ai.providers.gemini.key',
                'description' => 'Google Gemini API Key',
                'is_secret' => true,
            ],
            'openai_api_key' => [
                'config' => 'ai.providers.openai.key',
                'description' => 'OpenAI API Key',
                'is_secret' => true,
            ],
            'anthropic_api_key' => [
                'config' => 'ai.providers.anthropic.key',
                'description' => 'Anthropic API Key',
                'is_secret' => true,
            ],
        ]);

        $integrationSettings = $this->buildSettingsPayload('integrations', [
            'boostr_api_key' => [
                'config' => 'services.boostr.key',
                'description' => 'Boostr API Key (datos vehículo por patente)',
                'is_secret' => true,
            ],
            'boostr_base_url' => [
                'config' => 'services.boostr.base_url',
                'default' => 'https://api.boostr.cl',
                'description' => 'Boostr Base URL',
                'is_secret' => false,
            ],
        ]);

        $paymentSettings = $this->buildSettingsPayload('payments', [
            'mp_access_token' => [
                'description' => 'Mercado Pago Access Token',
                'is_secret' => true,
            ],
            'mp_public_key' => [
                'description' => 'Mercado Pago Public Key',
                'is_secret' => true,
            ],
            'mp_webhook_secret' => [
                'description' => 'Mercado Pago Webhook Secret',
                'is_secret' => true,
            ],
            'mp_sandbox' => [
                'default' => 'true',
                'description' => 'Modo sandbox de Mercado Pago',
                'is_secret' => false,
            ],
            'vat_rate' => [
                'config' => 'billing.vat_rate',
                'default' => '0.19',
                'description' => 'Tasa de IVA general aplicada a facturación y comisiones',
                'is_secret' => false,
            ],
        ]);

        $analyticsSettings = $this->buildSettingsPayload('analytics', [
            'analytics_google_analytics_code' => [
                'description' => 'Código de Google Analytics (Script de seguimiento)',
                'is_secret' => false,
            ],
            'analytics_google_search_console_code' => [
                'description' => 'Código de Verificación de Google Search Console (Meta Tag o HTML)',
                'is_secret' => false,
            ],
        ]);

        return Inertia::render('Admin/Profile', [
            'ai_settings' => $aiSettings,
            'integration_settings' => $integrationSettings,
            'payment_settings' => $paymentSettings,
            'analytics_settings' => $analyticsSettings,
        ]);
    }

    public function updateProfile(UpdateAdminProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $old = $user->only(['name', 'email']);

        $user->update($request->validated());

        AuditLog::record('profile.updated', 'Super-admin actualizó su perfil', $user, [
            'before' => $old,
            'after' => $user->only(['name', 'email']),
        ]);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        AuditLog::record('password.changed', 'Super-admin cambió su contraseña');

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function updateApiKeys(UpdateApiKeysRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('mp_sandbox', $data)) {
            $data['mp_sandbox'] = $request->boolean('mp_sandbox') ? 'true' : 'false';
        }

        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::set($key, $value);
            }
        }

        AuditLog::record('api_keys.updated', 'Super-admin actualizó las API keys del sistema');

        return back()->with('success', 'Configuración guardada correctamente.');
    }

    public function updateAnalytics(UpdateAnalyticsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLog::record('analytics_settings.updated', 'Super-admin actualizó la configuración de Analytics y Search Console desde perfil');

        return back()->with('success', 'Configuración de Analytics guardada correctamente.');
    }

    /**
     * @param  array<string, array{config?: string, default?: mixed, description: string, is_secret: bool}>  $definitions
     * @return Collection<string, array{value: mixed, description: string, is_secret: bool, has_value: bool}>
     */
    private function buildSettingsPayload(string $group, array $definitions): Collection
    {
        $storedSettings = Setting::getGroup($group)->keyBy('key');
        $payload = collect();

        foreach ($definitions as $key => $definition) {
            /** @var Setting|null $setting */
            $setting = $storedSettings->get($key);
            $configValue = array_key_exists('config', $definition) ? config($definition['config']) : null;
            $defaultValue = $definition['default'] ?? null;
            $resolvedValue = $setting?->value ?? $configValue ?? $defaultValue;
            $isSecret = $setting?->is_secret ?? $definition['is_secret'];

            $payload->put($key, [
                'value' => $isSecret ? (filled($resolvedValue) ? '••••••••' : null) : $resolvedValue,
                'description' => $setting?->description ?? $definition['description'],
                'is_secret' => $isSecret,
                'has_value' => filled($resolvedValue),
            ]);
        }

        return $payload;
    }
}
