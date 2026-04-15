<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_plans_index_is_accessible(): void
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.plans.index'));

        $response->assertOk();
    }

    public function test_plan_can_be_created(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.plans.store'), [
                'name' => 'Plan Test',
                'price_monthly' => 29990,
                'price_annual' => 299990,
                'features' => ['Feature 1', 'Feature 2'],
                'max_users' => 5,
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ]);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', ['name' => 'Plan Test', 'price_monthly' => 29990]);
    }

    public function test_plan_can_be_updated(): void
    {
        $plan = Plan::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.plans.update', $plan), [
                'name' => 'New Name',
                'price_monthly' => 39990,
                'price_annual' => 399990,
                'features' => ['New Feature'],
                'max_users' => 10,
                'trial_days' => 7,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ]);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'New Name']);
    }

    public function test_plan_can_be_deleted(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_unauthorized_user_cannot_access_plans(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)->get(route('admin.plans.index'))->assertForbidden();
    }
}
