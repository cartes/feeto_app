<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\TenantLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantAcquisitionReportTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_acquisition_report_shows_tenant_scoped_funnel_metrics(): void
    {
        $this->withoutVite();
        config(['analytics.track_testing_visits' => true]);

        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        foreach (range(1, 8) as $_) {
            $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]))->assertOk();
            $this->flushSession();
        }

        TenantLead::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'source' => TenantLead::SOURCE_WHATSAPP,
            'occurred_at' => now()->subDay(),
        ]);

        TenantLead::factory()->create([
            'tenant_id' => $tenant->id,
            'source' => TenantLead::SOURCE_CONTACT_GENERAL,
            'occurred_at' => now()->subHours(6),
        ]);

        TenantLead::factory()->create([
            'tenant_id' => $tenant->id,
            'source' => TenantLead::SOURCE_CONTACT_QUOTE,
            'occurred_at' => now()->subHours(3),
        ]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'plate' => 'AB1234',
            'customer_name' => 'Cliente Booking',
            'phone' => '+56911111111',
            'appointment_date' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $otherTenant = Tenant::factory()->create([
            'slug' => 'otro-taller',
        ]);

        foreach (range(1, 3) as $_) {
            $this->get(route('taller.landing', ['tenantBySlug' => $otherTenant->slug]))->assertOk();
            $this->flushSession();
        }

        $this->assertDatabaseHas('page_visits', [
            'tenant_id' => $tenant->id,
            'path' => 'taller/'.$tenant->slug,
            'unique_visits' => 8,
        ]);

        TenantLead::factory()->create([
            'tenant_id' => $otherTenant->id,
            'source' => TenantLead::SOURCE_WHATSAPP,
            'occurred_at' => now(),
        ]);

        Appointment::create([
            'tenant_id' => $otherTenant->id,
            'plate' => 'ZZ9999',
            'customer_name' => 'Cliente Externo',
            'phone' => '+56922222222',
            'appointment_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->actingAs($supervisor)
            ->get(route('reports.acquisition', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Acquisition')
                ->where('summary.unique_visits', 8)
                ->where('summary.whatsapp_leads', 2)
                ->where('summary.form_leads', 2)
                ->where('summary.booked_appointments', 1)
                ->where('summary.visit_to_engaged_rate', 50)
                ->where('summary.visit_to_booking_rate', 12.5)
                ->has('recentActivity', 5)
            );
    }
}
