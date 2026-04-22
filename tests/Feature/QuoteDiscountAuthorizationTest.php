<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class QuoteDiscountAuthorizationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_supervisor_can_apply_discount_above_tenant_threshold(): void
    {
        [$tenant, $workOrder] = $this->prepareWorkOrder();
        $tenant->update([
            'plan' => 'profesional',
            'max_discount_without_approval' => 5,
        ]);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor)
            ->post(route('work-orders.items.store', [
                'tenantBySlug' => $tenant->slug,
                'workOrder' => $workOrder->id,
            ]), [
                'description' => 'Diagnóstico avanzado',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_percent' => 12,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quote_items', [
            'description' => 'Diagnóstico avanzado',
            'discount_percent' => 12,
            'discount_amount' => 12000,
            'unit_price' => 88000,
            'total_price' => 88000,
        ]);
    }

    public function test_admin_cannot_apply_discount_above_tenant_threshold_without_approval_role(): void
    {
        [$tenant, $workOrder] = $this->prepareWorkOrder();
        $tenant->update([
            'plan' => 'profesional',
            'max_discount_without_approval' => 5,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->from(route('work-orders.show', ['tenantBySlug' => $tenant->slug, 'workOrder' => $workOrder->id]))
            ->post(route('work-orders.items.store', [
                'tenantBySlug' => $tenant->slug,
                'workOrder' => $workOrder->id,
            ]), [
                'description' => 'Diagnóstico avanzado',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_percent' => 12,
            ])
            ->assertRedirect(route('work-orders.show', ['tenantBySlug' => $tenant->slug, 'workOrder' => $workOrder->id]))
            ->assertSessionHasErrors(['discount_percent']);

        $this->assertDatabaseCount('quote_items', 0);
    }

    /**
     * @return array{0: Tenant, 1: WorkOrder}
     */
    private function prepareWorkOrder(): array
    {
        $tenant = $this->setUpTenant();

        $client = Client::create([
            'name' => 'Cliente Taller',
            'rut' => '12345678-5',
            'phone' => '+56911111111',
            'email' => 'cliente@feeto.test',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate' => 'TEST44',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $workOrder = WorkOrder::create([
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        return [$tenant, $workOrder];
    }
}
