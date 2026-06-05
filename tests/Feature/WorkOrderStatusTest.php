<?php

namespace Tests\Feature;

use App\Events\WorkOrderStatusUpdated;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class WorkOrderStatusTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private WorkOrder $workOrder;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();
        $tenant->update(['plan_type' => 'basico']);

        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->user->assignRole('Admin');

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

        $this->workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_ESPERANDO_REPUESTOS,
        ]);
    }

    public function test_kanban_includes_control_calidad_column(): void
    {
        $this->actingAs($this->user)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('kanban.control_calidad')
            );
    }

    public function test_work_order_can_move_to_control_calidad(): void
    {
        Event::fake([WorkOrderStatusUpdated::class]);

        $this->actingAs($this->user)
            ->put(route('work-orders.status.update', ['workOrder' => $this->workOrder->id]), [
                'status' => WorkOrder::STATUS_CONTROL_CALIDAD,
            ])
            ->assertRedirect();

        $this->assertSame(WorkOrder::STATUS_CONTROL_CALIDAD, $this->workOrder->refresh()->status);

        Event::assertDispatched(WorkOrderStatusUpdated::class);
    }

    public function test_unknown_work_order_status_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->from(route('work-orders.index'))
            ->put(route('work-orders.status.update', ['workOrder' => $this->workOrder->id]), [
                'status' => 'lavado',
            ])
            ->assertSessionHasErrors(['status']);

        $this->assertSame(WorkOrder::STATUS_ESPERANDO_REPUESTOS, $this->workOrder->refresh()->status);
    }

    public function test_moving_from_reception_without_accepted_quote_requires_confirmation(): void
    {
        $receptionWorkOrder = WorkOrder::create([
            'vehicle_id' => $this->workOrder->vehicle_id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        Quote::create([
            'work_order_id' => $receptionWorkOrder->id,
            'status' => Quote::STATUS_PENDING_CUSTOMER,
            'subtotal_amount' => 25000,
        ]);

        $this->actingAs($this->user)
            ->from(route('work-orders.index'))
            ->put(route('work-orders.status.update', ['workOrder' => $receptionWorkOrder->id]), [
                'status' => WorkOrder::STATUS_DIAGNOSTICO,
            ])
            ->assertRedirect(route('work-orders.index'))
            ->assertSessionHas('warning');

        $this->assertSame(WorkOrder::STATUS_RECEPCION, $receptionWorkOrder->fresh()->status);
    }

    public function test_moving_from_reception_without_accepted_quote_can_continue_after_confirmation(): void
    {
        Event::fake([WorkOrderStatusUpdated::class]);

        $receptionWorkOrder = WorkOrder::create([
            'vehicle_id' => $this->workOrder->vehicle_id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        Quote::create([
            'work_order_id' => $receptionWorkOrder->id,
            'status' => Quote::STATUS_REJECTED,
            'subtotal_amount' => 25000,
        ]);

        $this->actingAs($this->user)
            ->put(route('work-orders.status.update', ['workOrder' => $receptionWorkOrder->id]), [
                'status' => WorkOrder::STATUS_DIAGNOSTICO,
                'confirmed_without_accepted_quote' => true,
            ])
            ->assertRedirect();

        $this->assertSame(WorkOrder::STATUS_DIAGNOSTICO, $receptionWorkOrder->fresh()->status);

        Event::assertDispatched(WorkOrderStatusUpdated::class);
    }

    public function test_list_view_displays_paginated_orders(): void
    {
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data')
                ->where('kanban', [])
            );
    }

    public function test_list_view_can_be_filtered_by_month(): void
    {
        // Set creation dates of work orders
        $orderThisMonth = $this->workOrder;
        $orderThisMonth->created_at = now()->setDate(2026, 6, 1);
        $orderThisMonth->save();

        $orderLastMonth = WorkOrder::create([
            'vehicle_id' => $this->workOrder->vehicle_id,
            'status' => WorkOrder::STATUS_DIAGNOSTICO,
        ]);
        $orderLastMonth->created_at = now()->setDate(2026, 5, 1);
        $orderLastMonth->save();

        // Query for June 2026
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list', 'month' => '2026-06']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $orderThisMonth->id)
            );

        // Query for May 2026
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list', 'month' => '2026-05']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $orderLastMonth->id)
            );
    }

    public function test_list_view_can_be_filtered_by_search(): void
    {
        // Add a vehicle with unique plate and a different work order
        $client2 = Client::create([
            'name' => 'John Doe Unique',
            'rut' => '22222222-2',
        ]);
        $vehicle2 = Vehicle::create([
            'client_id' => $client2->id,
            'plate' => 'XYZ999',
            'brand' => 'Ferrari',
            'model' => 'Spider',
        ]);
        $order2 = WorkOrder::create([
            'vehicle_id' => $vehicle2->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        // Search by plate
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list', 'search' => 'XYZ999']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order2->id)
            );

        // Search by client name
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list', 'search' => 'Unique']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order2->id)
            );
    }

    public function test_list_view_can_set_custom_per_page(): void
    {
        // Create 12 more work orders
        for ($i = 0; $i < 12; $i++) {
            WorkOrder::create([
                'vehicle_id' => $this->workOrder->vehicle_id,
                'status' => WorkOrder::STATUS_DIAGNOSTICO,
            ]);
        }

        // We have 1 + 12 = 13 work orders total

        // Request 10 per page
        $this->actingAs($this->user)
            ->get(route('work-orders.index', ['view' => 'list', 'per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Index')
                ->has('orders.data', 10)
            );
    }

    public function test_admin_can_delete_work_order(): void
    {
        $this->actingAs($this->user)
            ->delete(route('work-orders.destroy', ['workOrder' => $this->workOrder->id]))
            ->assertRedirect(route('work-orders.index'))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('work_orders', [
            'id' => $this->workOrder->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_work_order(): void
    {
        $recepcionista = User::factory()->create([
            'tenant_id' => $this->user->tenant_id,
        ]);
        $recepcionista->assignRole('Recepcionista');

        $this->actingAs($recepcionista)
            ->delete(route('work-orders.destroy', ['workOrder' => $this->workOrder->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('work_orders', [
            'id' => $this->workOrder->id,
            'deleted_at' => null,
        ]);
    }
}
