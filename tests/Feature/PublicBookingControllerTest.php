<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AppointmentScheduledMail;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->tenant = Tenant::factory()->create(['slug' => 'test-taller']);
    }

    public function test_booking_page_loads(): void
    {
        $response = $this->get("/taller/{$this->tenant->slug}/");

        $response->assertOk()->assertInertia(fn ($page) => $page->component('Public/TenantLanding'));
    }

    public function test_booking_page_renders_schema_with_tenant_slug_in_canonical_url(): void
    {
        $response = $this->get(route('taller.landing', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('taller.landing', ['tenantBySlug' => $this->tenant->slug]).'">', false);
        $response->assertSee('"@type":"AutoRepair"', false);
        $response->assertSee('"@id":"'.route('taller.landing', ['tenantBySlug' => $this->tenant->slug]).'#business"', false);
        $response->assertSee('"url":"'.route('taller.landing', ['tenantBySlug' => $this->tenant->slug]).'"', false);
        $response->assertSee(url('/images/tallerflow-social-share.png'), false);
    }

    public function test_booking_can_be_created(): void
    {
        Mail::fake();

        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan Pérez',
            'phone' => '+56912345678',
            'plate' => 'AB1234',
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'pre_check_notes' => 'Cambio de aceite',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'plate' => 'AB1234',
            'customer_name' => 'Juan Pérez',
        ]);

        Mail::assertSent(AppointmentScheduledMail::class);
    }

    public function test_booking_rejects_conflicting_time_slot(): void
    {
        $date = now()->addDays(2)->setMinutes(0)->setSeconds(0);

        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'plate' => 'XY9999',
            'customer_name' => 'Cliente Previo',
            'phone' => '+56999999999',
            'appointment_date' => $date,
            'status' => 'pending',
        ]);

        // Intenta agendar 15 minutos después (dentro del margen de 30 min)
        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Otro Cliente',
            'phone' => '+56988888888',
            'plate' => 'AB1234',
            'appointment_date' => $date->copy()->addMinutes(15)->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasErrors(['appointment_date']);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_allows_slot_outside_conflict_window(): void
    {
        Mail::fake();

        $date = now()->addDays(2)->setMinutes(0)->setSeconds(0);

        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'plate' => 'XY9999',
            'customer_name' => 'Cliente Previo',
            'phone' => '+56999999999',
            'appointment_date' => $date,
            'status' => 'pending',
        ]);

        // 31 minutos después: debe permitirse
        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Otro Cliente',
            'phone' => '+56988888888',
            'plate' => 'AB5678',
            'appointment_date' => $date->copy()->addMinutes(31)->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('appointments', 2);

        Mail::assertSent(AppointmentScheduledMail::class);
    }

    public function test_booking_requires_valid_plate_format(): void
    {
        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan',
            'phone' => '+56912345678',
            'plate' => 'INVALID_TOO_LONG',
            'appointment_date' => now()->addDays(1)->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasErrors(['plate']);
    }

    public function test_booking_sends_email_to_tenant_configured_email(): void
    {
        Mail::fake();

        // Crear sucursal principal
        Branch::forceCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sucursal Principal',
            'email' => 'sucursal-principal@tallerflow.cl',
            'is_main' => true,
            'is_active' => true,
        ]);

        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan Pérez',
            'phone' => '+56912345678',
            'plate' => 'AB1234',
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'pre_check_notes' => 'Cambio de aceite',
        ]);

        $response->assertRedirect();

        Mail::assertSent(AppointmentScheduledMail::class, function (AppointmentScheduledMail $mail): bool {
            return $mail->hasTo('sucursal-principal@tallerflow.cl') &&
                $mail->appointment->customer_name === 'Juan Pérez' &&
                $mail->appointment->plate === 'AB1234';
        });
    }

    public function test_booking_sends_email_to_admin_fallback(): void
    {
        Mail::fake();

        // Sin sucursal, pero con admin
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin-taller@tallerflow.cl',
        ]);

        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan Pérez',
            'phone' => '+56912345678',
            'plate' => 'AB1234',
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'pre_check_notes' => 'Cambio de aceite',
        ]);

        $response->assertRedirect();

        Mail::assertSent(AppointmentScheduledMail::class, function (AppointmentScheduledMail $mail): bool {
            return $mail->hasTo('admin-taller@tallerflow.cl');
        });
    }

    public function test_booking_sends_bcc_to_admin_when_configured(): void
    {
        Mail::fake();
        config(['mail.admin_bcc' => 'contacto@tallerflow.cl']);

        Branch::forceCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sucursal Principal',
            'email' => 'sucursal-principal@tallerflow.cl',
            'is_main' => true,
            'is_active' => true,
        ]);

        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan Pérez',
            'phone' => '+56912345678',
            'plate' => 'AB1234',
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect();

        Mail::assertSent(AppointmentScheduledMail::class, function (AppointmentScheduledMail $mail): bool {
            return $mail->hasTo('sucursal-principal@tallerflow.cl') &&
                $mail->hasBcc('contacto@tallerflow.cl');
        });
    }

    public function test_booking_does_not_send_bcc_when_not_configured(): void
    {
        Mail::fake();
        config(['mail.admin_bcc' => null]);

        Branch::forceCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sucursal Principal',
            'email' => 'sucursal-principal@tallerflow.cl',
            'is_main' => true,
            'is_active' => true,
        ]);

        $response = $this->post("/taller/{$this->tenant->slug}/booking", [
            'customer_name' => 'Juan Pérez',
            'phone' => '+56912345678',
            'plate' => 'AB1234',
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect();

        Mail::assertSent(AppointmentScheduledMail::class, function (AppointmentScheduledMail $mail): bool {
            return $mail->hasTo('sucursal-principal@tallerflow.cl') && $mail->bcc === [];
        });
    }

    public function test_booking_page_passes_comuna_and_metadata_to_inertia(): void
    {
        $this->tenant->update([
            'comuna' => 'Providencia',
            'seo_address' => 'Av. Providencia 1234',
            'website_url' => 'https://mitaller.cl',
        ]);

        $response = $this->get("/taller/{$this->tenant->slug}/");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TenantLanding')
                ->where('tenant.comuna', 'Providencia')
                ->where('tenant.seo_address', 'Av. Providencia 1234')
                ->where('tenant.website_url', 'https://mitaller.cl')
            );
    }
}
