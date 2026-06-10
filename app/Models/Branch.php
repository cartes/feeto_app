<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, TenantAware;

    /** @var array<int, string> */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'is_active',
        'is_main',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    /**
     * Get the tenant that owns the branch.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the products for the branch.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the work orders for the branch.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Get the clients for the branch.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the clients through vehicles (branch -> vehicles -> clients).
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
