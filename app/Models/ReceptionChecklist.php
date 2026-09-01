<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionChecklist extends Model
{
    use TenantAware;

    protected $fillable = [
        'work_order_id',
        'fuel_level',
        'damages',
        'belongings',
        'notes',
        'signature_path',
        'signed_by_name',
        'signed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'fuel_level' => 'integer',
        'damages' => 'array',
        'belongings' => 'array',
        'signed_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
