<?php

namespace Tests\Feature;

use App\Events\MinimumMarginWarning;
use App\Events\PatentRecognized;
use App\Events\SafetyStockReached;
use App\Events\StockDepleted;
use App\Events\WorkOrderDraftCreated;
use App\Events\WorkOrderStatusUpdated;
use App\Models\Client;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BroadcastingEventsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Tenant $tenant;

    private WorkOrder $workOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->setUpTenant();

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
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);
    }

    public function test_patent_recognized_broadcasts_on_private_reception_channel(): void
    {
        $event = new PatentRecognized('ABC123', 'http://example.com/image.jpg', []);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('tenant.'.$this->tenant->id.'.reception', $channels[0]->name);
    }

    public function test_patent_recognized_broadcasts_correct_payload(): void
    {
        $vehicleData = ['brand' => 'Toyota', 'model' => 'Corolla', 'color' => 'Blanco'];
        $event = new PatentRecognized('ABC123', 'http://example.com/image.jpg', $vehicleData);

        $payload = $event->broadcastWith();

        $this->assertSame('ABC123', $payload['patente']);
        $this->assertSame('http://example.com/image.jpg', $payload['image_url']);
        $this->assertSame($vehicleData, $payload['vehicle']);
    }

    public function test_work_order_draft_created_broadcasts_on_private_work_orders_channel(): void
    {
        $event = new WorkOrderDraftCreated($this->workOrder);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('tenant.'.$this->tenant->id.'.work-orders', $channels[0]->name);
    }

    public function test_work_order_status_updated_broadcasts_on_private_taller_channel(): void
    {
        $event = new WorkOrderStatusUpdated(
            $this->workOrder,
            WorkOrder::STATUS_RECEPCION,
            WorkOrder::STATUS_DIAGNOSTICO
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('taller.'.$this->workOrder->tenant_id, $channels[0]->name);
    }

    public function test_work_order_status_updated_broadcasts_as_kanban_updated(): void
    {
        $event = new WorkOrderStatusUpdated(
            $this->workOrder,
            WorkOrder::STATUS_RECEPCION,
            WorkOrder::STATUS_DIAGNOSTICO
        );

        $this->assertSame('kanban.updated', $event->broadcastAs());
    }

    public function test_work_order_status_updated_broadcasts_correct_payload(): void
    {
        $event = new WorkOrderStatusUpdated(
            $this->workOrder,
            WorkOrder::STATUS_RECEPCION,
            WorkOrder::STATUS_DIAGNOSTICO
        );

        $payload = $event->broadcastWith();

        $this->assertSame($this->workOrder->id, $payload['work_order_id']);
        $this->assertSame(WorkOrder::STATUS_RECEPCION, $payload['old_status']);
        $this->assertSame(WorkOrder::STATUS_DIAGNOSTICO, $payload['new_status']);
    }

    public function test_stock_depleted_broadcasts_on_private_taller_channel(): void
    {
        $product = new Product(['name' => 'Producto Test', 'sku' => 'SKU-001']);
        $event = new StockDepleted($product, $this->tenant);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('taller.'.$this->tenant->id, $channels[0]->name);
    }

    public function test_stock_depleted_does_not_define_custom_broadcast_name(): void
    {
        $product = new Product(['name' => 'Producto Test', 'sku' => 'SKU-001']);
        $event = new StockDepleted($product, $this->tenant);

        $this->assertFalse(method_exists($event, 'broadcastAs'));
    }

    public function test_safety_stock_reached_broadcasts_on_private_taller_channel(): void
    {
        $product = new Product(['name' => 'Producto Test', 'sku' => 'SKU-001']);
        $event = new SafetyStockReached($product, $this->tenant);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('taller.'.$this->tenant->id, $channels[0]->name);
    }

    public function test_minimum_margin_warning_broadcasts_on_private_taller_channel(): void
    {
        $product = new Product(['name' => 'Producto Test', 'sku' => 'SKU-001']);
        $orderItem = new OrderItem(['quantity' => 1]);
        $event = new MinimumMarginWarning($product, $orderItem, $this->tenant);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringContainsString('taller.'.$this->tenant->id, $channels[0]->name);
    }
}
