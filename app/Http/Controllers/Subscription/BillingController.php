<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(): Response
    {
        $tenant = Tenant::current();
        abort_if(! $tenant, 404);

        $payments = Payment::where('tenant_id', $tenant->id)
            ->with('plan:id,name')
            ->latest()
            ->paginate(12)
            ->through(fn (Payment $p): array => [
                'id' => $p->id,
                'plan_name' => $p->plan?->name ?? '—',
                'amount' => $p->amount,
                'billing_period' => $p->billing_period,
                'status' => $p->status,
                'mp_payment_type' => $p->mp_payment_type,
                'mp_fee_total' => $p->mpFeeTotal(),
                'paid_at' => $p->paid_at?->toIso8601String(),
                'created_at' => $p->created_at->toIso8601String(),
            ]);

        $approvedQuery = Payment::where('tenant_id', $tenant->id)
            ->where('status', Payment::STATUS_APPROVED);

        $summary = [
            'total_paid' => (int) $approvedQuery->sum('amount'),
            'count_approved' => $approvedQuery->count(),
            'next_renewal' => $tenant->subscription_ends_at?->toIso8601String(),
        ];

        return Inertia::render('Subscription/Billing', [
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }
}
