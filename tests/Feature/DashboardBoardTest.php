<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
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
                ->where('board.workshop.total', 3)
                ->where('board.workshop.stages', fn ($stages) => collect($stages)->firstWhere('status', 'recepcion')['count'] === 2
                    && collect($stages)->firstWhere('status', 'listo')['count'] === 1)
                ->has('board.workshop.vehicles', 3)
                ->where('board.low_stock.total', 2)
                ->where('board.low_stock.items.0.name', 'Aceite agotado')
                ->where('board.low_stock.items.0.depleted', true)
                ->where('board.low_stock.items.1.name', 'Filtro bajo')
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
                ->where('board.workshop.total', 0)
                ->where('board.low_stock.total', 0)
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
                ->where('board.workshop', null)
                ->where('board.low_stock', null)
            );
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        return $admin;
    }
}
