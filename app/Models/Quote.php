<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasFactory, TenantAware;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_CUSTOMER = 'pending_customer';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'client_id',
        'vehicle_id',
        'uuid',
        'status',
        'subtotal_amount',
        'apply_tax',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'notes',
        'sent_at',
        'responded_at',
        'customer_response_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'apply_tax' => 'boolean',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->uuid)) {
                $quote->uuid = (string) Str::uuid();
            }

            if (! isset($quote->attributes['apply_tax'])) {
                $quote->apply_tax = true;
            }

            if (! isset($quote->attributes['tax_rate'])) {
                $tenant = Tenant::current() ?? ($quote->tenant_id ? Tenant::find($quote->tenant_id) : null);
                $quote->tax_rate = $tenant?->defaultTaxRate() ?? 19.00;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taxName(): string
    {
        $tenant = $this->tenant ?? (Tenant::current() ?? ($this->tenant_id ? Tenant::find($this->tenant_id) : null));

        return $tenant?->taxName() ?? 'IVA';
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isManual(): bool
    {
        return ! is_null($this->client_id);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuoteEvent::class);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_CUSTOMER,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ];
    }
}
