<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
        Storage::fake('public');
    }

    /** @test */
    public function test_admin_can_update_tenant_branding_color(): void
    {
        $tenant = Tenant::factory()->create(['primary_color' => '#FF7A00']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $response = $this->patch(route('taller.settings.branding.color', ['tenantBySlug' => $tenant->slug]), [
            'primary_color' => '#123456',
        ]);

        $response->assertRedirect();
        $this->assertEquals('#123456', $tenant->fresh()->primary_color);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_cannot_update_branding_color_with_invalid_hex(): void
    {
        $tenant = Tenant::factory()->create(['primary_color' => '#FF7A00']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $response = $this->patch(route('taller.settings.branding.color', ['tenantBySlug' => $tenant->slug]), [
            'primary_color' => 'invalid-color',
        ]);

        $response->assertSessionHasErrors('primary_color');
        $this->assertEquals('#FF7A00', $tenant->fresh()->primary_color);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_non_admin_cannot_update_tenant_branding_color(): void
    {
        $tenant = Tenant::factory()->create(['primary_color' => '#FF7A00']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant);

        $tenant->makeCurrent();
        $user->assignRole('Mecanico');
        $this->actingAs($user);

        $response = $this->patch(route('taller.settings.branding.color', ['tenantBySlug' => $tenant->slug]), [
            'primary_color' => '#123456',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('#FF7A00', $tenant->fresh()->primary_color);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_admin_can_upload_tenant_logo(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $logo = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->post(route('taller.settings.branding.logo', ['tenantBySlug' => $tenant->slug]), [
            'logo' => $logo,
        ]);

        $response->assertRedirect();

        $tenant->refresh();
        $this->assertNotNull($tenant->logo_path);

        Storage::disk('public')->assertExists($tenant->logo_path);

        Tenant::forgetCurrent();
    }

    /** @test */
    public function test_admin_can_delete_tenant_logo(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();
        $this->actingAs($admin);

        $logoPath = "tenants/{$tenant->id}/logo.webp";
        Storage::disk('public')->put($logoPath, 'fake-logo-content');
        $tenant->update(['logo_path' => $logoPath]);

        $response = $this->delete(route('taller.settings.branding.logo.delete', ['tenantBySlug' => $tenant->slug]));

        $response->assertRedirect();

        $tenant->refresh();
        $this->assertNull($tenant->logo_path);
        Storage::disk('public')->assertMissing($logoPath);

        Tenant::forgetCurrent();
    }
}
