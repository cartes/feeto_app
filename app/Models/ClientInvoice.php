<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientInvoice extends Model
{
    use HasFactory, TenantAware;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'work_order_id',
        'quote_id',
        'invoice_number',
        'status',
        'amount_total',
        'amount_due',
        'issued_at',
        'due_at',
        'paid_at',
        'last_whatsapp_reminder_sent_at',
        'whatsapp_reminder_count',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount_total' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'issued_at' => 'date',
        'due_at' => 'date',
        'paid_at' => 'datetime',
        'last_whatsapp_reminder_sent_at' => 'datetime',
        'whatsapp_reminder_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function isOverdue(): bool
    {
        return (float) $this->amount_due > 0
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIAL, self::STATUS_OVERDUE], true)
            && $this->due_at->isPast();
    }

    public function markReminderSent(): void
    {
        $this->update([
            'status' => $this->isOverdue() ? self::STATUS_OVERDUE : $this->status,
            'last_whatsapp_reminder_sent_at' => now(),
            'whatsapp_reminder_count' => $this->whatsapp_reminder_count + 1,
        ]);
    }
}
