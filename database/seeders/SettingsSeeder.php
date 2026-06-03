<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // AI providers
            ['key' => 'ai_provider', 'group' => 'ai', 'description' => 'Proveedor IA activo para texto', 'is_secret' => false, 'value' => 'gemini'],
            ['key' => 'ai_image_provider', 'group' => 'ai', 'description' => 'Proveedor IA activo para imágenes (OCR)', 'is_secret' => false, 'value' => 'gemini'],
            ['key' => 'gemini_api_key', 'group' => 'ai', 'description' => 'Google Gemini API Key', 'is_secret' => true, 'value' => null],
            ['key' => 'openai_api_key', 'group' => 'ai', 'description' => 'OpenAI API Key', 'is_secret' => true, 'value' => null],
            ['key' => 'anthropic_api_key', 'group' => 'ai', 'description' => 'Anthropic API Key', 'is_secret' => true, 'value' => null],

            // Integrations
            ['key' => 'boostr_api_key', 'group' => 'integrations', 'description' => 'Boostr API Key (datos vehículo por patente)', 'is_secret' => true, 'value' => null],
            ['key' => 'boostr_base_url', 'group' => 'integrations', 'description' => 'Boostr Base URL', 'is_secret' => false, 'value' => 'https://api.boostr.cl'],

            // Mercado Pago
            ['key' => 'mp_access_token', 'group' => 'payments', 'description' => 'Mercado Pago Access Token', 'is_secret' => true, 'value' => null],
            ['key' => 'mp_public_key', 'group' => 'payments', 'description' => 'Mercado Pago Public Key', 'is_secret' => true, 'value' => null],
            ['key' => 'mp_webhook_secret', 'group' => 'payments', 'description' => 'Mercado Pago Webhook Secret', 'is_secret' => true, 'value' => null],
            ['key' => 'mp_sandbox', 'group' => 'payments', 'description' => 'Modo sandbox de Mercado Pago', 'is_secret' => false, 'value' => 'true'],

            // Analytics
            ['key' => 'analytics_google_analytics_code', 'group' => 'analytics', 'description' => 'Código de Google Analytics (Script de seguimiento)', 'is_secret' => false, 'value' => null],
            ['key' => 'analytics_google_search_console_code', 'group' => 'analytics', 'description' => 'Código de Verificación de Google Search Console (Meta Tag o HTML)', 'is_secret' => false, 'value' => null],

            // Marketing
            ['key' => 'marketing_whatsapp_enabled', 'group' => 'marketing', 'description' => 'Activa el botón flotante global de WhatsApp en las páginas públicas', 'is_secret' => false, 'value' => null],
            ['key' => 'marketing_whatsapp_number', 'group' => 'marketing', 'description' => 'Número de WhatsApp del super-admin para leads orgánicos', 'is_secret' => false, 'value' => null],
            ['key' => 'marketing_whatsapp_message', 'group' => 'marketing', 'description' => 'Mensaje inicial del botón flotante global de WhatsApp', 'is_secret' => false, 'value' => null],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
