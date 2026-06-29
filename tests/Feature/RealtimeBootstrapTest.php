<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RealtimeBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverb_config_is_disabled_in_the_view_when_broadcasting_is_not_reverb(): void
    {
        Config::set('broadcasting.default', 'log');
        Config::set('broadcasting.connections.reverb.key', 'reverb-key');

        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->get(route('receptions.create', [
            'tenantBySlug' => $tenant->slug,
        ]));

        $response->assertOk();
        $response->assertSee('enabled: false', false);
        $response->assertDontSee('reverb-key', false);
    }

    public function test_reverb_config_is_enabled_in_the_view_when_reverb_is_the_active_broadcaster(): void
    {
        Config::set('broadcasting.default', 'reverb');
        Config::set('broadcasting.connections.reverb.key', 'reverb-key');

        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->get(route('receptions.create', [
            'tenantBySlug' => $tenant->slug,
        ]));

        $response->assertOk();
        $response->assertSee('enabled: true', false);
        $response->assertSee('reverb-key', false);
    }

    public function test_reverb_config_is_disabled_on_public_pages_even_when_broadcasting_is_active(): void
    {
        Config::set('broadcasting.default', 'reverb');
        Config::set('broadcasting.connections.reverb.key', 'reverb-key');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('enabled: false', false);
        $response->assertDontSee('reverb-key', false);
    }

    private function setUpTenant(): Tenant
    {
        $tenant = Tenant::firstOrCreate(
            ['rut_taller' => '12345678-9'],
            [
                'name' => 'Taller Test',
                'slug' => 'taller-test',
                'domain' => 'test.tallerflow.test',
            ]
        );

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        return $tenant;
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('Admin');

        return $admin;
    }
}
