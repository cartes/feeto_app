<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PublicCheckoutControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->tenant = $this->setUpTenant();
    }

    public function test_checkout_page_is_accessible_without_authentication(): void
    {
        $response = $this->get(route('checkout.show', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertOk();
    }

    public function test_checkout_page_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->get(route('checkout.show', ['tenantBySlug' => 'no-existe-este-slug']));

        $response->assertNotFound();
    }

    public function test_checkout_page_renders_with_plans_and_tenant(): void
    {
        Plan::factory()->count(3)->create(['is_active' => true]);
        Plan::factory()->create(['is_active' => false]);

        $response = $this->get(route('checkout.show', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Checkout')
            ->has('plans', 3)
            ->has('tenant')
            ->where('tenant.slug', $this->tenant->slug)
        );
    }

    public function test_preference_validates_required_fields(): void
    {
        $response = $this->post(
            route('checkout.preference', ['tenantBySlug' => $this->tenant->slug]),
            []
        );

        $response->assertSessionHasErrors(['plan_id', 'billing_period']);
    }

    public function test_preference_validates_billing_period_values(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->post(
            route('checkout.preference', ['tenantBySlug' => $this->tenant->slug]),
            ['plan_id' => $plan->id, 'billing_period' => 'trimestral']
        );

        $response->assertSessionHasErrors(['billing_period']);
    }

    public function test_preference_returns_error_without_mp_access_token(): void
    {
        Setting::where('key', 'mp_access_token')->delete();
        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->post(
            route('checkout.preference', ['tenantBySlug' => $this->tenant->slug]),
            ['plan_id' => $plan->id, 'billing_period' => 'monthly']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_success_redirects_back_to_checkout_with_flash(): void
    {
        $response = $this->get(route('checkout.success', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('checkout.show', ['tenantBySlug' => $this->tenant->slug]));
        $response->assertSessionHas('success');
    }

    public function test_failure_redirects_back_to_checkout_with_flash(): void
    {
        $response = $this->get(route('checkout.failure', ['tenantBySlug' => $this->tenant->slug]));

        $response->assertRedirect(route('checkout.show', ['tenantBySlug' => $this->tenant->slug]));
        $response->assertSessionHas('error');
    }
}
