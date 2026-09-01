<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'amount' => fake()->numberBetween(9990, 99990),
            'currency' => 'CLP',
            'billing_period' => Payment::BILLING_MONTHLY,
            'status' => Payment::STATUS_PENDING,
            'method' => 'mercadopago',
            'mp_preference_id' => 'pref-'.fake()->uuid(),
            'mp_fee_net' => 0,
            'mp_fee_vat' => 0,
            'net_amount' => 0,
        ];
    }
}
