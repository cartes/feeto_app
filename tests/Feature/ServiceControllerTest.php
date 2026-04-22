<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ServiceControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();
        $plan = Plan::factory()->create([
            'feature_keys' => [PlanFeatureService::FEATURE_COMMERCIAL_QUOTES],
        ]);
        $tenant->update(['plan_id' => $plan->id]);

        $this->admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_create_a_service(): void
    {
        $this->actingAs($this->admin)
            ->post(route('services.store'), [
                'name' => 'Cambio de aceite',
                'code' => 'SERV-100',
                'description' => 'Servicio de mantención',
                'cost_price' => 10000,
                'selling_price' => 25000,
                'estimated_minutes' => 60,
                'is_active' => true,
            ])
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'name' => 'Cambio de aceite',
            'code' => 'SERV-100',
        ]);
    }

    public function test_admin_can_update_a_service(): void
    {
        $service = Service::create([
            'name' => 'Alineación',
            'code' => 'SERV-200',
            'cost_price' => 5000,
            'selling_price' => 18000,
            'estimated_minutes' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('services.update', ['service' => $service->id]), [
                'name' => 'Alineación y balanceo',
                'code' => 'SERV-200',
                'description' => 'Servicio actualizado',
                'cost_price' => 7000,
                'selling_price' => 22000,
                'estimated_minutes' => 50,
                'is_active' => false,
            ])
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Alineación y balanceo',
            'is_active' => false,
        ]);
    }

    public function test_service_module_is_blocked_when_plan_does_not_include_it(): void
    {
        $tenant = $this->admin->tenant()->firstOrFail();
        $tenant->update([
            'plan_id' => Plan::factory()->create(['feature_keys' => []])->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('services.index'))
            ->assertForbidden();
    }
}
