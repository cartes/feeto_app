<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_page_renders_server_side_social_meta_with_default_image(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:image"', false);
        $response->assertSee(url('/images/tallerflow-social-share.png'), false);
        $response->assertSee('name="twitter:image"', false);
    }

    public function test_home_page_uses_configured_social_image_when_available(): void
    {
        Setting::set('seo_home_og_image', 'https://cdn.example.com/tallerflow-social-share.png');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://cdn.example.com/tallerflow-social-share.png', false);
    }

    public function test_public_tenant_landing_renders_server_side_social_meta_tags(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Norte',
            'slug' => 'taller-norte',
            'seo_description' => 'Especialistas en mantenciones, diagnóstico y reparación automotriz.',
        ]);

        $response = $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertSee('Agendar Cita | Taller Norte', false);
        $response->assertSee('Especialistas en mantenciones, diagnóstico y reparación automotriz.', false);
        $response->assertSee(url('/images/tallerflow-social-share.png'), false);
    }
}
