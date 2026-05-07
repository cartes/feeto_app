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
}
