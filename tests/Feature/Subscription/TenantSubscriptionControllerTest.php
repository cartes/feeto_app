<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantSubscriptionControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->tenant = $this->setUpTenant();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->admin->assignRole('Admin');
    }

    public function test_plans_page_requires_authentication(): void
    {
        $response = $this->get(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('login'));
    }

    public function test_plans_page_renders_for_tenant_admin(): void
    {
        Plan::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Subscription/Plans')
            ->has('plans')
            ->has('currentPlanId')
            ->has('subscriptionEndsAt')
            ->has('isActive')
        );
    }

    public function test_plans_page_shows_only_active_plans(): void
    {
        Plan::factory()->count(2)->create(['is_active' => true]);
        Plan::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertInertia(fn ($page) => $page->has('plans', 2));
    }

    public function test_create_preference_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('subscription.preference', ['tenantBySlug' => $this->tenant->slug]), []);

        $response->assertSessionHasErrors(['plan_id', 'billing_period']);
    }

    public function test_create_preference_validates_billing_period_values(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->post(route('subscription.preference', ['tenantBySlug' => $this->tenant->slug]), [
                'plan_id' => $plan->id,
                'billing_period' => 'quarterly',
            ]);

        $response->assertSessionHasErrors(['billing_period']);
    }

    public function test_create_preference_returns_error_without_mp_access_token(): void
    {
        Setting::where('key', 'mp_access_token')->delete();

        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->post(route('subscription.preference', ['tenantBySlug' => $this->tenant->slug]), [
                'plan_id' => $plan->id,
                'billing_period' => 'monthly',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_from_different_tenant_cannot_access_subscription(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($otherUser)
            ->post(route('subscription.preference', ['tenantBySlug' => $this->tenant->slug]), [
                'plan_id' => Plan::factory()->create()->id,
                'billing_period' => 'monthly',
            ]);

        $response->assertForbidden();
    }

    public function test_success_callback_redirects_with_success_flash(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('subscription.success', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));
        $response->assertSessionHas('success');
    }

    public function test_failure_callback_redirects_with_error_flash(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('subscription.failure', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));
        $response->assertSessionHas('error');
    }

    public function test_pending_callback_redirects_with_info_flash(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('subscription.pending', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('subscription.plans', ['tenantBySlug' => $this->tenant->slug]));
        $response->assertSessionHas('info');
    }
}
