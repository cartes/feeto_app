<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\PlanFeatureService;
use App\Traits\BelongsToTenantAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use BelongsToTenantAndSoftDeletes, HasFactory;

    public const STATUS_RECEPCION = 'recepcion';

    public const STATUS_DIAGNOSTICO = 'diagnostico';

    public const STATUS_ESPERANDO_REPUESTOS = 'esperando_repuestos';

    public const STATUS_CONTROL_CALIDAD = 'control_calidad';

    public const STATUS_LISTO = 'listo';

    protected $fillable = [
        'vehicle_id',
        'branch_id',
        'status',
        'observations',
        'uuid',
        'total_amount',
        'notified_statuses',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'notified_statuses' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder) {
            if (empty($workOrder->uuid)) {
                $workOrder->uuid = (string) Str::uuid();
            }

            if (empty($workOrder->notified_statuses)) {
                $workOrder->notified_statuses = [$workOrder->status];
            }
        });
    }

    /**
     * Indica si ya se notificó al cliente sobre el estado dado.
     */
    public function hasBeenNotifiedFor(string $status): bool
    {
        return in_array($status, $this->notified_statuses ?? [], true);
    }

    /**
     * Marca un estado como notificado al cliente.
     */
    public function markStatusAsNotified(string $status): void
    {
        $notifiedStatuses = $this->notified_statuses ?? [];

        if (in_array($status, $notifiedStatuses, true)) {
            return;
        }

        $notifiedStatuses[] = $status;

        $this->update(['notified_statuses' => $notifiedStatuses]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(WorkOrderImage::class);
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(ReceptionChecklist::class);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        $tenant = Tenant::current();

        if ($tenant && $tenant->hasFeature(PlanFeatureService::FEATURE_CUSTOM_KANBAN)) {
            if (! empty($tenant->kanban_columns)) {
                return $tenant->kanban_columns;
            }

            return [
                self::STATUS_RECEPCION,
                self::STATUS_DIAGNOSTICO,
                self::STATUS_ESPERANDO_REPUESTOS,
                self::STATUS_CONTROL_CALIDAD,
                self::STATUS_LISTO,
            ];
        }

        return [
            self::STATUS_RECEPCION,
            'taller',
            'aviso_cliente',
            self::STATUS_LISTO,
        ];
    }
}
