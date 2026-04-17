<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use HasFactory, TenantAware;

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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder) {
            if (empty($workOrder->uuid)) {
                $workOrder->uuid = (string) Str::uuid();
            }
        });
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

    public function images(): HasMany
    {
        return $this->hasMany(WorkOrderImage::class);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_RECEPCION,
            self::STATUS_DIAGNOSTICO,
            self::STATUS_ESPERANDO_REPUESTOS,
            self::STATUS_CONTROL_CALIDAD,
            self::STATUS_LISTO,
        ];
    }
}
