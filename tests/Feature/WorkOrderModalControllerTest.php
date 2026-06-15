<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class WorkOrderModalControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private Tenant $tenantA;

    private User $adminA;

    private WorkOrder $workOrderA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = $this->setUpTenant(); // provisions roles/permissions and sets domain/slug

        $this->adminA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
        ]);
        $this->adminA->assignRole('Admin');

        $clientA = Client::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Cliente Tenant A',
            'rut' => '11111111-1',
        ]);

        $vehicleA = Vehicle::create([
            'client_id' => $clientA->id,
            'plate' => 'AAAA11',
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $this->workOrderA = WorkOrder::create([
            'vehicle_id' => $vehicleA->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);
    }

    public function test_tenant_admin_can_view_own_work_order_modal_details(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('api.work-orders.show', [
                'tenantBySlug' => $this->tenantA->slug,
                'id' => $this->workOrderA->id,
            ]))
            ->assertOk()
            ->assertJsonPath('id', $this->workOrderA->id);
    }

    public function test_tenant_admin_cannot_view_other_tenant_work_order_modal_details(): void
    {
        // Setup Tenant B
        $tenantB = Tenant::create([
            'name' => 'Taller B',
            'slug' => 'taller-b',
            'domain' => 'b.tallerflow.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();

        $clientB = Client::create([
            'name' => 'Cliente Tenant B',
            'rut' => '22222222-2',
        ]);

        $vehicleB = Vehicle::create([
            'client_id' => $clientB->id,
            'plate' => 'BBBB22',
            'brand' => 'Suzuki',
            'model' => 'Swift',
        ]);

        $workOrderB = WorkOrder::create([
            'vehicle_id' => $vehicleB->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        $this->tenantA->makeCurrent();

        // adminA (Tenant A) tries to access tenant B's route
        $this->actingAs($this->adminA)
            ->get(route('api.work-orders.show', [
                'tenantBySlug' => $tenantB->slug,
                'id' => $workOrderB->id,
            ]))
            ->assertForbidden();

        // adminA (Tenant A) tries to access own route but passing Tenant B's work order ID
        // This will result in 404 ModelNotFound because of TenantAware global scope
        $this->actingAs($this->adminA)
            ->get(route('api.work-orders.show', [
                'tenantBySlug' => $this->tenantA->slug,
                'id' => $workOrderB->id,
            ]))
            ->assertNotFound();
    }

    public function test_super_admin_can_view_any_work_order_modal_details(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('api.work-orders.show', [
                'tenantBySlug' => $this->tenantA->slug,
                'id' => $this->workOrderA->id,
            ]))
            ->assertOk()
            ->assertJsonPath('id', $this->workOrderA->id);
    }

    public function test_tenant_admin_cannot_upload_image_to_other_tenant_work_order(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Taller B',
            'slug' => 'taller-b',
            'domain' => 'b.tallerflow.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();

        $clientB = Client::create([
            'name' => 'Cliente Tenant B',
            'rut' => '22222222-2',
        ]);

        $vehicleB = Vehicle::create([
            'client_id' => $clientB->id,
            'plate' => 'BBBB22',
            'brand' => 'Suzuki',
            'model' => 'Swift',
        ]);

        $workOrderB = WorkOrder::create([
            'vehicle_id' => $vehicleB->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        $this->tenantA->makeCurrent();

        Storage::fake('local');

        $this->actingAs($this->adminA)
            ->post(route('api.work-orders.images.upload', [
                'tenantBySlug' => $tenantB->slug,
                'id' => $workOrderB->id,
            ]), [
                'image' => UploadedFile::fake()->image('evidence.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_upload_image_to_any_work_order(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        Storage::fake('local');

        $this->actingAs($superAdmin)
            ->post(route('api.work-orders.images.upload', [
                'tenantBySlug' => $this->tenantA->slug,
                'id' => $this->workOrderA->id,
            ]), [
                'image' => UploadedFile::fake()->image('evidence.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Imagen subida con éxito');
    }

    public function test_tenant_admin_cannot_delete_image_of_other_tenant_work_order(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Taller B',
            'slug' => 'taller-b',
            'domain' => 'b.tallerflow.test',
            'rut_taller' => '22222222-2',
        ]);

        $tenantB->makeCurrent();

        $clientB = Client::create([
            'name' => 'Cliente Tenant B',
            'rut' => '22222222-2',
        ]);

        $vehicleB = Vehicle::create([
            'client_id' => $clientB->id,
            'plate' => 'BBBB22',
            'brand' => 'Suzuki',
            'model' => 'Swift',
        ]);

        $workOrderB = WorkOrder::create([
            'vehicle_id' => $vehicleB->id,
            'status' => WorkOrder::STATUS_RECEPCION,
        ]);

        $imageB = $workOrderB->images()->create([
            'image_path' => 'tenants/'.$tenantB->id.'/work_orders/imagenes/evidence.jpg',
        ]);

        $this->tenantA->makeCurrent();

        $this->actingAs($this->adminA)
            ->delete(route('api.work-orders.images.destroy', [
                'tenantBySlug' => $tenantB->slug,
                'imageId' => $imageB->id,
            ]))
            ->assertForbidden();
    }

    public function test_super_admin_can_delete_image_of_any_work_order(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $imageA = $this->workOrderA->images()->create([
            'image_path' => 'tenants/'.$this->tenantA->id.'/work_orders/imagenes/evidence.jpg',
        ]);

        Storage::fake('local');

        $this->actingAs($superAdmin)
            ->delete(route('api.work-orders.images.destroy', [
                'tenantBySlug' => $this->tenantA->slug,
                'imageId' => $imageA->id,
            ]))
            ->assertOk()
            ->assertJsonPath('message', 'Imagen eliminada con éxito');
    }
}
