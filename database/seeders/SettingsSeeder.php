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
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
