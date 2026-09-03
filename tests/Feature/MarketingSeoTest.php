<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MarketingSeoPages;
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

    public function test_home_page_preloads_the_lcp_hero_image(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="preload"', false);
        $response->assertSee('as="image"', false);
        $response->assertSee(url('/images/car-hero.webp'), false);
    }

    public function test_non_home_public_pages_do_not_preload_the_home_hero_image(): void
    {
        $response = $this->get('/servicios');

        $response->assertOk();
        $response->assertDontSee(url('/images/car-hero.webp'), false);
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
        $response->assertSee('Agendamiento - Taller Norte', false);
        $response->assertSee('Especialistas en mantenciones, diagnóstico y reparación automotriz.', false);
        $response->assertSee(url('/images/tallerflow-social-share.png'), false);
    }

    public function test_public_tenant_landing_includes_website_backlink_in_schema(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Sur',
            'slug' => 'taller-sur',
            'seo_description' => 'Mecánica general y diagnóstico.',
            'website_url' => 'https://www.tallersur.cl',
        ]);

        $response = $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"sameAs"', false);
        $response->assertSee('https://www.tallersur.cl', false);
        $response->assertInertia(fn ($page) => $page
            ->where('tenant.website_url', 'https://www.tallersur.cl')
            ->where('seo.title', 'Agendamiento - Taller Sur')
        );
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

    public function test_public_pages_do_not_emit_duplicate_og_type_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'property="og:type"'));
        $response->assertSee('property="og:type" content="website"', false);
        $response->assertSee('property="og:site_name" content="TallerFlow"', false);
        $response->assertSee('property="og:locale" content="es_CL"', false);
        $response->assertSee('name="robots" content="index, follow, max-image-preview:large, max-snippet:-1"', false);
    }

    public function test_pages_without_seo_block_are_marked_noindex(): void
    {
        $response = $this->get(route('trial.success'));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
        $response->assertDontSee('property="og:title"', false);
    }

    public function test_orden_de_trabajo_guide_uses_article_metadata_and_editable_seo(): void
    {
        Setting::set('seo_orden_trabajo_title', 'Guía OT personalizada');

        $response = $this->get('/orden-de-trabajo-taller-mecanico');

        $response->assertOk();
        $response->assertSee('<title inertia>Guía OT personalizada</title>', false);
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('"@type":"Article"', false);
        $response->assertInertia(fn ($page) => $page->where('seo.title', 'Guía OT personalizada'));
    }

    public function test_services_and_directory_pages_use_editable_seo(): void
    {
        Setting::set('seo_services_description', 'Descripción servicios editada.');
        Setting::set('seo_talleres_title', 'Directorio editado');

        $this->get('/servicios')
            ->assertOk()
            ->assertSee('name="description" content="Descripción servicios editada."', false);

        $this->get('/talleres')
            ->assertOk()
            ->assertSee('<title inertia>Directorio editado</title>', false);
    }

    public function test_organization_schema_logo_points_to_an_existing_asset(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(url('/images/taller-flow-isotipo.png'), false);
        $this->assertFileExists(public_path('images/taller-flow-isotipo.png'));
    }

    public function test_inactive_tenant_landing_is_marked_noindex(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'taller-pausado',
            'is_active' => false,
        ]);

        $response = $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_super_admin_can_update_seo_for_every_marketing_page(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $pages = collect(MarketingSeoPages::keys())->map(fn (string $key) => [
            'key' => $key,
            'title' => "Título {$key}",
            'description' => "Descripción {$key}",
        ])->all();

        $this->actingAs($admin)
            ->put(route('admin.landing-seo.update'), ['pages' => $pages])
            ->assertRedirect();

        $this->assertSame('Título services', Setting::get('seo_services_title'));
        $this->assertSame('Descripción talleres', Setting::get('seo_talleres_description'));
    }
}
