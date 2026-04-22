<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_ai_reception_endpoints_are_forbidden_when_feature_is_not_enabled(): void
    {
        [$tenant, $admin] = $this->createTenantWithPlan('gratuito');

        $this->actingAs($admin);

        $message = app(PlanFeatureService::class)->upgradeMessage(PlanFeatureService::FEATURE_AI_RECEPTION);

        $this->postJson(route('receptions.store', ['tenantBySlug' => $tenant->slug]))
            ->assertForbidden()
            ->assertJson(['message' => $message]);

        $this->postJson(route('ocr.process', ['tenantBySlug' => $tenant->slug]))
            ->assertForbidden()
            ->assertJson(['message' => $message]);

        $this->postJson(route('api.appointments.scan-plate', ['tenantBySlug' => $tenant->slug]))
            ->assertForbidden()
            ->assertJson(['message' => $message]);
    }

    public function test_appointments_page_shows_basic_schedule_data_for_plan_without_calendar_feature(): void
    {
        [$tenant, $admin] = $this->createTenantWithPlan('basico');

        Appointment::create([
            'tenant_id' => $tenant->id,
            'plate' => 'AB1234',
            'customer_name' => 'Cliente Básico',
            'phone' => '+56911111111',
            'appointment_date' => now()->setTime(10, 0),
            'status' => 'pending',
            'notes' => 'Cambio de aceite',
        ]);

        $this->actingAs($admin)
            ->get(route('appointments.index', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('planAccess.calendar_scheduling', false)
                ->where('planAccess.ai_reception', true)
                ->has('appointments', 1)
                ->has('todayAppointments', 1)
                ->has('appointmentNotifications', 1)
            );
    }

    public function test_dashboard_and_agenda_share_advanced_schedule_data_for_professional_plan(): void
    {
        [$tenant, $admin] = $this->createTenantWithPlan('profesional');

        Appointment::create([
            'tenant_id' => $tenant->id,
            'plate' => 'CD5678',
            'customer_name' => 'Cliente Pro',
            'phone' => '+56922222222',
            'appointment_date' => now()->setTime(15, 30),
            'status' => 'pending',
            'notes' => 'Diagnóstico inicial',
        ]);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('planAccess.calendar_scheduling', true)
                ->where('planAccess.ai_reception', true)
                ->has('appointments', 1)
                ->has('todayAppointments', 1)
                ->has('appointmentNotifications', 1)
            );

        $this->actingAs($admin)
            ->get(route('appointments.index', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('planAccess.calendar_scheduling', true)
                ->where('planAccess.ai_reception', true)
                ->has('appointments', 1)
                ->has('todayAppointments', 1)
                ->has('appointmentNotifications', 1)
            );
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createTenantWithPlan(string $plan): array
    {
        $tenant = Tenant::factory()->create([
            'plan' => $plan,
            'plan_type' => $plan,
        ]);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        return [$tenant, $admin];
    }
}
