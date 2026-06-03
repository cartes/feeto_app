<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class SettingCacheFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_setting_value_when_cache_store_fails(): void
    {
        Setting::create([
            'key' => 'seo_home_title',
            'value' => 'Titulo desde BD',
            'group' => 'seo',
            'description' => 'Home SEO title',
            'is_secret' => false,
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new RuntimeException('cache unavailable'));

        $this->assertSame('Titulo desde BD', Setting::get('seo_home_title', 'Fallback'));
    }

    public function test_it_updates_setting_even_when_cache_forget_fails(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->andThrow(new RuntimeException('cache unavailable'));

        Setting::set('seo_home_title', 'Nuevo titulo');

        $this->assertSame('Nuevo titulo', Setting::query()->where('key', 'seo_home_title')->firstOrFail()->value);
    }
}
