<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BillingControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->tenant = $this->setUpTenant();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->admin->assignRole('Admin');
        $this->plan = Plan::factory()->create();
    }

    public function test_billing_page_requires_authentication(): void
    {
        $this->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_billing_page_renders_for_tenant_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subscription/Billing')
                ->has('payments')
                ->has('summary')
                ->has('summary.total_paid')
                ->has('summary.count_approved')
                ->has('summary.next_renewal')
            );
    }

    public function test_only_returns_payments_belonging_to_current_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();

        Payment::factory()->create(['tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id]);
        Payment::factory()->create(['tenant_id' => $otherTenant->id, 'plan_id' => $this->plan->id]);

        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page->has('payments.data', 1));
    }

    public function test_internal_financial_fields_are_not_exposed(): void
    {
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'mp_fee_net' => 350,
            'mp_fee_vat' => 66,
            'net_amount' => 9574,
            'transaction_id' => 'TXN-SECRET-123',
        ]);

        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page
                ->has('payments.data.0')
                ->missing('payments.data.0.mp_fee_net')
                ->missing('payments.data.0.mp_fee_vat')
                ->missing('payments.data.0.net_amount')
                ->missing('payments.data.0.transaction_id')
            );
    }

    public function test_tenant_without_payments_receives_empty_data_and_zero_total(): void
    {
        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 0)
                ->where('summary.total_paid', 0)
                ->where('summary.count_approved', 0)
            );
    }

    public function test_summary_total_paid_counts_only_approved_payments(): void
    {
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 9990,
            'status' => Payment::STATUS_APPROVED,
        ]);
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 9990,
            'status' => Payment::STATUS_PENDING,
        ]);
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 9990,
            'status' => Payment::STATUS_REJECTED,
        ]);

        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page
                ->where('summary.total_paid', 9990)
                ->where('summary.count_approved', 1)
            );
    }

    public function test_pagination_splits_13_payments_into_2_pages(): void
    {
        Payment::factory()->count(13)->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 12)
                ->where('payments.last_page', 2)
                ->where('payments.total', 13)
            );
    }

    public function test_projected_fields_are_present(): void
    {
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'billing_period' => Payment::BILLING_MONTHLY,
        ]);

        $this->actingAs($this->admin)
            ->get(route('subscription.billing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn ($page) => $page
                ->has('payments.data.0.id')
                ->has('payments.data.0.plan_name')
                ->has('payments.data.0.amount')
                ->has('payments.data.0.billing_period')
                ->has('payments.data.0.status')
                ->has('payments.data.0.mp_payment_type')
                ->has('payments.data.0.mp_fee_total')
                ->has('payments.data.0.paid_at')
                ->has('payments.data.0.created_at')
            );
    }
}
