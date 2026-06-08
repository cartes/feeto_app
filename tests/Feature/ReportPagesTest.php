<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ReportPagesTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_reports_index_lists_all_available_reports_for_the_tenant(): void
    {
        $this->withoutVite();

        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor)
            ->get(route('reports.index', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('reports', 5)
                ->where('reports.2.route', 'reports.inventory')
                ->where('reports.3.route', 'reports.customers')
                ->where('reports.4.route', 'reports.collections')
            );
    }

    public function test_high_priority_reports_render_expected_summary_data(): void
    {
        $this->withoutVite();

        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        Product::create([
            'name' => 'Filtro de Aceite',
            'sku' => 'REP-001',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'physical_stock' => 0,
            'reserved_stock' => 0,
            'min_stock' => 2,
        ]);

        Product::create([
            'name' => 'Pastillas de Freno',
            'sku' => 'REP-002',
            'cost_price' => 20000,
            'selling_price' => 30000,
            'physical_stock' => 3,
            'reserved_stock' => 1,
            'min_stock' => 5,
        ]);

        Product::create([
            'name' => 'Batería 12V',
            'sku' => 'REP-003',
            'cost_price' => 50000,
            'selling_price' => 70000,
            'physical_stock' => 12,
            'reserved_stock' => 4,
            'min_stock' => 3,
        ]);

        $clientA = Client::create([
            'name' => 'Cliente Activo',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
            'email' => 'activo@example.com',
            'max_credit_limit' => 500000,
        ]);

        $clientB = Client::create([
            'name' => 'Cliente Dormido',
            'rut' => '22222222-2',
            'phone' => '+56922222222',
            'email' => 'dormido@example.com',
            'max_credit_limit' => 250000,
        ]);

        $vehicleA = Vehicle::create([
            'client_id' => $clientA->id,
            'plate' => 'AA1111',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $vehicleB = Vehicle::create([
            'client_id' => $clientB->id,
            'plate' => 'BB2222',
            'brand' => 'Kia',
            'model' => 'Rio',
        ]);

        $activeWorkOrder = WorkOrder::create([
            'vehicle_id' => $vehicleA->id,
            'status' => WorkOrder::STATUS_LISTO,
            'total_amount' => 120000,
        ]);
        $activeWorkOrder->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->saveQuietly();

        $inactiveWorkOrder = WorkOrder::create([
            'vehicle_id' => $vehicleB->id,
            'status' => WorkOrder::STATUS_LISTO,
            'total_amount' => 60000,
        ]);
        $inactiveWorkOrder->forceFill([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ])->saveQuietly();

        ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $clientA->id,
            'work_order_id' => $activeWorkOrder->id,
            'invoice_number' => 'FAC-1-0001',
            'status' => ClientInvoice::STATUS_OVERDUE,
            'amount_total' => 50000,
            'amount_due' => 50000,
            'issued_at' => now()->subDays(20)->toDateString(),
            'due_at' => now()->subDays(10)->toDateString(),
            'whatsapp_reminder_count' => 2,
        ]);

        ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $clientA->id,
            'work_order_id' => $activeWorkOrder->id,
            'invoice_number' => 'FAC-1-0002',
            'status' => ClientInvoice::STATUS_OVERDUE,
            'amount_total' => 15000,
            'amount_due' => 15000,
            'issued_at' => now()->subDays(50)->toDateString(),
            'due_at' => now()->subDays(40)->toDateString(),
        ]);

        ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $clientB->id,
            'work_order_id' => $inactiveWorkOrder->id,
            'invoice_number' => 'FAC-1-0003',
            'status' => ClientInvoice::STATUS_PENDING,
            'amount_total' => 25000,
            'amount_due' => 25000,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($supervisor)
            ->get(route('reports.inventory', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Inventory')
                ->where('summary.total_products', 3)
                ->where('summary.critical_products', 1)
                ->where('summary.low_stock_products', 1)
                ->where('summary.reserved_units', 5)
            );

        $this->actingAs($supervisor)
            ->get(route('reports.customers', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Customers')
                ->where('summary.total_clients', 2)
                ->where('summary.active_clients', 1)
                ->where('summary.inactive_clients', 1)
                ->where('summary.clients_with_overdue_invoices', 1)
                ->where('summary.lifetime_value', 180000)
            );

        $this->actingAs($supervisor)
            ->get(route('reports.collections', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Collections')
                ->where('summary.total_invoices', 3)
                ->where('summary.open_invoices', 3)
                ->where('summary.overdue_invoices', 2)
                ->where('summary.overdue_amount', 65000)
                ->has('topDebtors', 1)
            );
    }
}
