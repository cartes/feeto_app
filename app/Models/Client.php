<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'rut',
        'name',
        'phone',
        'email',
        'max_credit_limit',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'max_credit_limit' => 'decimal:2',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }
}
