<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiSettingsBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_settings_override_ai_and_boostr_configuration(): void
    {
        Config::set('ai.default', 'openai');
        Config::set('ai.default_for_images', 'openai');
        Config::set('ai.providers.gemini.key', null);
        Config::set('services.boostr.key', null);
        Config::set('services.boostr.base_url', 'https://fallback.example');

        Setting::create([
            'key' => 'ai_provider',
            'value' => 'anthropic',
            'group' => 'ai',
            'description' => 'Proveedor IA activo para texto',
            'is_secret' => false,
        ]);

        Setting::create([
            'key' => 'ai_image_provider',
            'value' => 'gemini',
            'group' => 'ai',
            'description' => 'Proveedor IA activo para imágenes (OCR)',
            'is_secret' => false,
        ]);

        Setting::create([
            'key' => 'gemini_api_key',
            'value' => 'gemini-from-settings',
            'group' => 'ai',
            'description' => 'Google Gemini API Key',
            'is_secret' => true,
        ]);

        Setting::create([
            'key' => 'boostr_api_key',
            'value' => 'boostr-from-settings',
            'group' => 'integrations',
            'description' => 'Boostr API Key',
            'is_secret' => true,
        ]);

        Setting::create([
            'key' => 'boostr_base_url',
            'value' => 'https://custom.boostr.test',
            'group' => 'integrations',
            'description' => 'Boostr Base URL',
            'is_secret' => false,
        ]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('anthropic', config('ai.default'));
        $this->assertSame('gemini', config('ai.default_for_images'));
        $this->assertSame('gemini-from-settings', config('ai.providers.gemini.key'));
        $this->assertSame('boostr-from-settings', config('services.boostr.key'));
        $this->assertSame('https://custom.boostr.test', config('services.boostr.base_url'));
    }
}
