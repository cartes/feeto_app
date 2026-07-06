<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderStatusChangedNotification;
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

    /** @test */
    public function test_client_emails_render_tenant_branding_when_tenant_is_current(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Centrado',
            'slug' => 'taller-centrado',
            'logo_path' => 'tenants/1/logo.webp',
            'primary_color' => '#E53E3E',
        ]);

        Storage::disk('public')->put('tenants/1/logo.webp', 'fake content');

        $tenant->makeCurrent();

        // 1. Test AppointmentConfirmationMail
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_name' => 'Juan Perez',
            'appointment_date' => now()->addDays(2),
        ]);

        $mailable = new AppointmentConfirmationMail($appointment, $tenant);
        $html = $mailable->render();

        $this->assertStringContainsString('Taller Centrado', $html);
        $this->assertStringContainsString('tenants/1/logo.webp', $html);
        $this->assertStringContainsString('#E53E3E', $html);

        // 2. Test WorkOrderStatusChangedNotification
        $client = Client::create([
            'name' => 'Cliente Test',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
            'email' => 'test@example.com',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'TEST01',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        $notification = new WorkOrderStatusChangedNotification(
            $workOrder,
            WorkOrder::STATUS_RECEPCION,
            WorkOrder::STATUS_DIAGNOSTICO
        );

        $mailMessage = $notification->toMail((object) []);
        $notificationHtml = (string) $mailMessage->render();

        $this->assertStringContainsString('Taller Centrado', $notificationHtml);
        $this->assertStringContainsString('tenants/1/logo.webp', $notificationHtml);
        $this->assertStringContainsString('#E53E3E', $notificationHtml);

        Tenant::forgetCurrent();
    }
}
