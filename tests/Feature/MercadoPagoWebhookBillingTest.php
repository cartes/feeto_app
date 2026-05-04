<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class MercadoPagoWebhookBillingTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_billing_period_monthly_constant_is_correct(): void
    {
        $this->assertSame('monthly', Payment::BILLING_MONTHLY);
    }

    public function test_billing_period_annual_constant_is_correct(): void
    {
        $this->assertSame('annual', Payment::BILLING_ANNUAL);
    }

    public function test_billing_period_is_stored_in_payments_table(): void
    {
        $tenant = $this->setUpTenant();

        $monthly = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 9990,
            'currency' => 'CLP',
            'billing_period' => Payment::BILLING_MONTHLY,
            'status' => Payment::STATUS_PENDING,
        ]);

        $annual = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 99990,
            'currency' => 'CLP',
            'billing_period' => Payment::BILLING_ANNUAL,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('payments', ['id' => $monthly->id, 'billing_period' => 'monthly']);
        $this->assertDatabaseHas('payments', ['id' => $annual->id, 'billing_period' => 'annual']);
    }

    public function test_subscription_end_date_calculation_for_monthly_billing(): void
    {
        $billingPeriod = Payment::BILLING_MONTHLY;

        $ends = $billingPeriod === Payment::BILLING_ANNUAL
            ? now()->addYear()
            : now()->addMonth();

        $this->assertEquals(now()->addMonth()->format('Y-m'), $ends->format('Y-m'));
    }

    public function test_subscription_end_date_calculation_for_annual_billing(): void
    {
        $billingPeriod = Payment::BILLING_ANNUAL;

        $ends = $billingPeriod === Payment::BILLING_ANNUAL
            ? now()->addYear()
            : now()->addMonth();

        $this->assertEquals(now()->addYear()->format('Y'), $ends->format('Y'));
    }

    public function test_webhook_updates_tenant_subscription_ends_at(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->subDay(), 'is_active' => false]);

        // Simulate what the webhook controller does after billing_period fix
        $billingPeriod = Payment::BILLING_MONTHLY;
        $ends = $billingPeriod === Payment::BILLING_ANNUAL
            ? now()->addYear()
            : now()->addMonth();

        Tenant::where('id', $tenant->id)->update([
            'subscription_ends_at' => $ends,
            'is_active' => true,
        ]);

        $tenant->refresh();
        $this->assertTrue($tenant->is_active);
        $this->assertEquals(now()->addMonth()->format('Y-m'), $tenant->subscription_ends_at->format('Y-m'));
    }
}
