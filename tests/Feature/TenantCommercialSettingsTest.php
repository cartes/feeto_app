<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantCommercialSettingsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_admin_can_update_tenant_discount_threshold(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->patch(route('taller.settings.commercial.update', ['tenantBySlug' => $tenant->slug]), [
                'max_discount_without_approval' => 18.5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'max_discount_without_approval' => 18.5,
        ]);
    }

    public function test_professional_plan_users_can_access_sales_and_supervisor_reports(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor)
            ->get(route('reports.sales', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Sales'));

        $this->actingAs($supervisor)
            ->get(route('reports.supervisors', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Supervisors'));
    }

    public function test_dashboard_shows_overdue_invoices_only_for_users_with_financials_permission(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $client = Client::create([
            'name' => 'Cliente Mora',
            'rut' => '11111111-1',
            'phone' => '+56912345678',
            'email' => 'cliente@example.com',
            'max_credit_limit' => 500000,
        ]);

        ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-1-0001',
            'status' => ClientInvoice::STATUS_OVERDUE,
            'amount_total' => 150000,
            'amount_due' => 150000,
            'issued_at' => now()->subDays(10)->toDateString(),
            'due_at' => now()->subDays(3)->toDateString(),
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $mecanico = User::factory()->create(['tenant_id' => $tenant->id]);
        $mecanico->assignRole('Mecanico');

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('overdueInvoices.0')
            );

        $this->actingAs($mecanico)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('overdueInvoices', [])
            );
    }
}
