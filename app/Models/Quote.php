<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'status',
        'subtotal_amount',
        'sent_at',
        'responded_at',
        'customer_response_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
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
