<?php

namespace Tests\Feature;

use App\Events\WorkOrderStatusUpdated;
use App\Models\Client;
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

        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

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
}
