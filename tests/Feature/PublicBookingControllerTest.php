<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['slug' => 'test-taller']);
    }

    public function test_booking_page_loads(): void
    {
        $response = $this->get("/taller/{$this->tenant->slug}/");

        $response->assertOk()->assertInertia(fn ($page) => $page->component('Public/TenantLanding'));
    }

    public function test_booking_can_be_created(): void
    {
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
}
