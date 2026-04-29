<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenantAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToTenantAndSoftDeletes, HasFactory;

    protected $fillable = [
        'client_id',
        'plate',
        'brand',
        'model',
        'color',
        'vin',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
