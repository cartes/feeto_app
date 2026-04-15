<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'plan_id', 'amount', 'currency', 'status',
    'method', 'transaction_id', 'mp_preference_id', 'mp_payment_id',
    'mp_fee_net', 'mp_fee_vat', 'net_amount', 'mp_payment_type', 'mp_installments',
    'paid_at', 'metadata',
])]
class Payment extends Model
{
    /** @var array<string, string> */
    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'integer',
        'mp_fee_net' => 'integer',
        'mp_fee_vat' => 'integer',
        'net_amount' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /** Comisión total MP (neto + IVA) */
    public function mpFeeTotal(): int
    {
        return $this->mp_fee_net + $this->mp_fee_vat;
    }

    /**
     * Calcula y asigna comisiones a partir del monto bruto de MP.
     * En Chile el IVA de la comisión es 19%.
     *
     * @param  float  $mpFeeWithVat  Comisión total cobrada por MP (incluye IVA)
     */
    public function calculateFees(float $mpFeeWithVat): void
    {
        $vatRate = 0.19;
        $feeNet = (int) round($mpFeeWithVat / (1 + $vatRate));
        $feeVat = (int) round($mpFeeWithVat) - $feeNet;

        $this->mp_fee_net = $feeNet;
        $this->mp_fee_vat = $feeVat;
        $this->net_amount = $this->amount - $feeNet - $feeVat;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
