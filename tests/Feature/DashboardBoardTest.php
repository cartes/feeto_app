<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class DashboardBoardTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_board_lists_workshop_stages_and_low_stock_products(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        WorkOrder::factory()->count(2)->create(['tenant_id' => $tenant->id]);
        WorkOrder::factory()->listo()->create(['tenant_id' => $tenant->id]);

        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Filtro bajo', 'physical_stock' => 2, 'min_stock' => 5]);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Aceite agotado', 'physical_stock' => 0, 'min_stock' => 5]);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Bujía ok', 'physical_stock' => 20, 'min_stock' => 5]);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->missing('board')
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->where('board.workshop.total', 3)
                    ->where('board.workshop.stages', fn ($stages) => collect($stages)->firstWhere('status', 'recepcion')['count'] === 2
                        && collect($stages)->firstWhere('status', 'listo')['count'] === 1)
                    ->has('board.workshop.vehicles', 3)
                    ->where('board.low_stock.total', 2)
                    ->where('board.low_stock.items.0.name', 'Aceite agotado')
                    ->where('board.low_stock.items.0.depleted', true)
                    ->where('board.low_stock.items.1.name', 'Filtro bajo'))
            );
    }

    public function test_board_vehicles_include_a_whatsapp_notice_with_the_tracking_link(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $client = Client::factory()->create(['tenant_id' => $tenant->id, 'name' => 'María Pérez', 'phone' => '+56 9 1234 5678']);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'brand' => 'Toyota', 'model' => 'Yaris', 'plate' => 'ABCD12']);
        $ready = WorkOrder::factory()->listo()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle->id]);
        $inProgress = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle->id]);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->where('board.workshop.vehicles', function ($vehicles) use ($ready, $inProgress, $tenant): bool {
                        $vehicles = collect($vehicles)->keyBy('id');
                        $readyCard = $vehicles[$ready->id];
                        $progressCard = $vehicles[$inProgress->id];
                        $readyText = urldecode(parse_url($readyCard['whatsapp_url'], PHP_URL_QUERY));

                        return $readyCard['client_name'] === 'María Pérez'
                            && $readyCard['needs_notice'] === true
                            && str_starts_with($readyCard['whatsapp_url'], 'https://wa.me/56912345678?text=')
                            && str_contains($readyText, 'Hola María')
                            && str_contains($readyText, 'listo para retiro')
                            && str_contains($readyText, route('tracking.show', ['uuid' => $ready->uuid]))
                            && $readyCard['phone_url'] === 'tel:+56912345678'
                            && $progressCard['needs_notice'] === false
                            && str_contains(urldecode(parse_url($progressCard['whatsapp_url'], PHP_URL_QUERY)), 'seguir el avance')
                            && str_contains($readyText, $tenant->name);
                    }))
            );
    }

    public function test_board_vehicles_without_a_client_phone_have_no_contact_links(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $client = Client::factory()->create(['tenant_id' => $tenant->id, 'phone' => null]);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        WorkOrder::factory()->listo()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle->id]);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->where('board.workshop.vehicles.0.whatsapp_url', null)
                    ->where('board.workshop.vehicles.0.phone_url', null)
                    ->where('board.workshop.vehicles.0.needs_notice', true))
            );
    }

    public function test_board_does_not_include_other_tenants_data(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);
        $otherTenant = Tenant::factory()->create();

        WorkOrder::factory()->create(['tenant_id' => $otherTenant->id]);
        Product::factory()->create(['tenant_id' => $otherTenant->id, 'physical_stock' => 0, 'min_stock' => 5]);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->where('board.workshop.total', 0)
                    ->where('board.low_stock.total', 0))
            );
    }

    public function test_board_hides_sections_the_user_has_no_permission_for(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->where('board.workshop', null)
                    ->where('board.low_stock', null))
            );
    }

    public function test_tenant_pages_include_the_appearance_bootstrap_script(): void
    {
        $this->withoutVite();
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertSee("localStorage.getItem('appearance')", false);
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        return $admin;
    }
}
