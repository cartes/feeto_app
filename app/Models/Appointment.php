<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'vehicle_id',
        'plate',
        'customer_name',
        'phone',
        'appointment_date',
        'status',
        'notes',
        'pre_check_notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
