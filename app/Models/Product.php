<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenantAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToTenantAndSoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'type',
        'name',
        'sku',
        'description',
        'cost_price',
        'selling_price',
        'physical_stock',
        'reserved_stock',
        'min_stock',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'physical_stock' => 'integer',
        'reserved_stock' => 'integer',
        'min_stock' => 'integer',
    ];

    /**
     * Get the tenant that owns the product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch that owns the product.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Determina si el stock está en nivel critico.
     */
    public function isLowStock(): bool
    {
        return $this->physical_stock <= $this->min_stock;
    }
}
