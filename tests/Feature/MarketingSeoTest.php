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

    public function test_home_page_receives_configured_marketing_whatsapp_settings(): void
    {
        Setting::set('marketing_whatsapp_enabled', true);
        Setting::set('marketing_whatsapp_number', '+56 9 1234 5678');
        Setting::set('marketing_whatsapp_message', 'Hola, vi TallerFlow y quiero una demo.');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('marketing_whatsapp.is_enabled', true)
            ->where('marketing_whatsapp.is_ready', true)
            ->where('marketing_whatsapp.number', '+56 9 1234 5678')
            ->where('marketing_whatsapp.sanitized_number', '56912345678')
            ->where('marketing_whatsapp.message', 'Hola, vi TallerFlow y quiero una demo.')
            ->where('marketing_whatsapp.href', 'https://wa.me/56912345678?text=Hola%2C%20vi%20TallerFlow%20y%20quiero%20una%20demo.')
        );
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

    public function test_home_page_includes_json_ld_schema_markup(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('type="application/ld+json"', false);
        $response->assertSee('"@type":"SoftwareApplication"', false);
        $response->assertSee('"@type":"Organization"', false);
    }

    public function test_public_tenant_landing_includes_json_ld_schema_markup(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Sur',
            'slug' => 'taller-sur',
        ]);

        $response = $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertSee('type="application/ld+json"', false);
        $response->assertSee('"@type":"AutoRepair"', false);
        $response->assertSee('"@type":"ReserveAction"', false);
    }
}
