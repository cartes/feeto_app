<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\InternalNote;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ClientControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_show_displays_a_unified_crm_profile(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $client = $this->createClient([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Cliente',
            'rut' => '11111111-1',
            'phone' => '+56912345678',
            'email' => 'juan@example.com',
            'max_credit_limit' => 350000,
        ]);

        $firstVehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'AA1111',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'color' => 'Rojo',
            'vin' => 'VIN111',
        ]);

        $secondVehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'BB2222',
            'brand' => 'Mazda',
            'model' => 'CX5',
            'color' => 'Azul',
            'vin' => 'VIN222',
        ]);

        $olderWorkOrder = WorkOrder::create([
            'vehicle_id' => $firstVehicle->id,
            'status' => WorkOrder::STATUS_DIAGNOSTICO,
            'observations' => 'Revisión general',
            'total_amount' => 0,
        ]);
        $olderWorkOrder->forceFill([
            'created_at' => Carbon::parse('2026-04-25 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-25 10:00:00'),
        ])->saveQuietly();

        $deliveredWorkOrder = WorkOrder::create([
            'vehicle_id' => $firstVehicle->id,
            'status' => WorkOrder::STATUS_LISTO,
            'observations' => 'Cambio de aceite y filtros',
            'total_amount' => 185000,
        ]);
        $deliveredWorkOrder->forceFill([
            'created_at' => Carbon::parse('2026-04-28 09:00:00'),
            'updated_at' => Carbon::parse('2026-04-28 09:00:00'),
        ])->saveQuietly();

        Quote::create([
            'tenant_id' => $tenant->id,
            'work_order_id' => $olderWorkOrder->id,
            'status' => Quote::STATUS_REJECTED,
            'subtotal_amount' => 45000,
            'sent_at' => Carbon::parse('2026-04-25 12:00:00'),
        ]);

        Quote::create([
            'tenant_id' => $tenant->id,
            'work_order_id' => $deliveredWorkOrder->id,
            'status' => Quote::STATUS_ACCEPTED,
            'subtotal_amount' => 185000,
            'sent_at' => Carbon::parse('2026-04-28 10:00:00'),
            'responded_at' => Carbon::parse('2026-04-28 13:00:00'),
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'vehicle_id' => $secondVehicle->id,
            'plate' => $secondVehicle->plate,
            'customer_name' => $client->name,
            'phone' => $client->phone,
            'appointment_date' => Carbon::parse('2026-04-29 11:30:00'),
            'status' => 'pending',
            'notes' => 'Cliente solicita revisión de frenos',
            'pre_check_notes' => 'Ruido al frenar',
        ]);

        $note = InternalNote::create([
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'content' => 'Cliente VIP con seguimiento mensual.',
        ]);
        $note->forceFill([
            'created_at' => Carbon::parse('2026-04-30 08:15:00'),
            'updated_at' => Carbon::parse('2026-04-30 08:15:00'),
        ])->saveQuietly();

        ClientInvoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'work_order_id' => $deliveredWorkOrder->id,
            'invoice_number' => 'FAC-0001',
            'status' => ClientInvoice::STATUS_PENDING,
            'amount_total' => 185000,
            'amount_due' => 50000,
            'issued_at' => '2026-04-28',
            'due_at' => '2026-04-30',
        ]);

        $this->actingAs($admin)
            ->get(route('clients.show', [
                'tenantBySlug' => $tenant->slug,
                'client' => $client->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Clients/Show')
                ->where('client.id', $client->id)
                ->where('crmMetrics.total_spent', fn ($value) => (float) $value === 185000.0)
                ->where('crmMetrics.average_ticket', fn ($value) => (float) $value === 185000.0)
                ->where('crmMetrics.vehicles_count', 2)
                ->where('crmMetrics.visits_count', 3)
                ->where('crmMetrics.notes_count', 1)
                ->where('crmMetrics.last_visit', $appointment->appointment_date->toISOString())
                ->where('quoteSummary.total_quotes', 2)
                ->where('quoteSummary.accepted_quotes', 1)
                ->where('quoteSummary.rejected_quotes', 1)
                ->where('invoiceSummary.total_invoices', 1)
                ->where('invoiceSummary.overdue_invoices', 1)
                ->has('vehicles', 2)
                ->where('vehicles.0.plate', 'AA1111')
                ->where('vehicles.0.work_orders_count', 2)
                ->has('historyTimeline', 4)
                ->where('historyTimeline.0.type', 'internal_note')
                ->where('historyTimeline.1.type', 'appointment')
                ->where('historyTimeline.1.status_label', 'pendiente')
                ->where('historyTimeline.2.type', 'work_order')
                ->where('historyTimeline.2.amount', fn ($value) => (float) $value === 185000.0)
                ->where('historyTimeline.3.type', 'work_order')
                ->where('internalNotes.0.user.name', $admin->name)
            );
    }

    public function test_index_includes_crm_signals_for_each_client(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $client = $this->createClient([
            'tenant_id' => $tenant->id,
            'name' => 'Maria CRM',
            'rut' => '22222222-2',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'CC3333',
            'brand' => 'Kia',
            'model' => 'Rio',
        ]);

        WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_LISTO,
            'total_amount' => 99000,
        ])->forceFill([
            'created_at' => Carbon::parse('2026-05-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-05-01 10:00:00'),
        ])->saveQuietly();

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'plate' => $vehicle->plate,
            'customer_name' => $client->name,
            'phone' => '+56912345678',
            'appointment_date' => Carbon::parse('2026-05-03 12:00:00'),
            'status' => 'arrived',
        ]);

        InternalNote::create([
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'content' => 'Llamar para revisión post servicio.',
        ]);

        $this->actingAs($admin)
            ->get(route('clients.index', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Clients/Index')
                ->where('clients.data.0.id', $client->id)
                ->where('clients.data.0.metrics.vehicles_count', 1)
                ->where('clients.data.0.metrics.visits_count', 2)
                ->where('clients.data.0.metrics.notes_count', 1)
                ->where('clients.data.0.metrics.total_spent', fn ($value) => (float) $value === 99000.0)
                ->where('clients.data.0.metrics.last_visit', $appointment->appointment_date->toISOString())
            );
    }

    public function test_notes_store_persists_an_internal_note_for_the_current_tenant(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $client = $this->createClient([
            'tenant_id' => $tenant->id,
            'rut' => '33333333-3',
            'name' => 'Pedro Notas',
        ]);

        $this->actingAs($admin)
            ->post(route('clients.notes.store', [
                'tenantBySlug' => $tenant->slug,
                'client' => $client->id,
            ]), [
                'content' => 'Recordar ofrecer mantención anual.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internal_notes', [
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'content' => 'Recordar ofrecer mantención anual.',
        ]);
    }

    public function test_a_client_from_another_tenant_cannot_be_accessed(): void
    {
        $tenantA = $this->setUpTenant();
        $admin = $this->createAdmin($tenantA);

        $tenantB = $this->createTenant([
            'name' => 'Taller Dos',
            'slug' => 'taller-dos',
            'domain' => 'dos.tallerflow.test',
            'rut_taller' => '99999999-9',
        ]);

        $tenantB->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenantB->slug]);

        $foreignClient = $this->createClient([
            'tenant_id' => $tenantB->id,
            'rut' => '44444444-4',
            'name' => 'Cliente Externo',
        ]);

        $tenantA->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenantA->slug]);

        $this->actingAs($admin)
            ->get(route('clients.show', [
                'tenantBySlug' => $tenantA->slug,
                'client' => $foreignClient->id,
            ]))
            ->assertNotFound();
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('Admin');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createClient(array $attributes): Client
    {
        return Client::create(array_merge([
            'rut' => '99999999-9',
            'name' => 'Cliente Test',
            'phone' => null,
            'email' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTenant(array $attributes): Tenant
    {
        $tenant = Tenant::create($attributes);

        app(TenantSetupService::class)->provisionTenant($tenant);

        return $tenant;
    }
}
