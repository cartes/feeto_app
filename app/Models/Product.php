<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenantAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenantAndSoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'category_id',
        'type',
        'name',
        'sku',
        'description',
        'cost_price',
        'selling_price',
        'tax_included',
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
        'tax_included' => 'boolean',
        'physical_stock' => 'integer',
        'reserved_stock' => 'integer',
        'min_stock' => 'integer',
    ];

    public function netSellingPrice(?float $taxRate = null): float
    {
        $rate = $taxRate ?? ($this->tenant?->defaultTaxRate() ?? 19.0);
        $price = (float) $this->selling_price;

        if ($this->tax_included && $rate > 0) {
            return round($price / (1 + ($rate / 100)), 2);
        }

        return $price;
    }

    public function grossSellingPrice(?float $taxRate = null): float
    {
        $rate = $taxRate ?? ($this->tenant?->defaultTaxRate() ?? 19.0);
        $price = (float) $this->selling_price;

        if (! $this->tax_included && $rate > 0) {
            return round($price * (1 + ($rate / 100)), 2);
        }

        return $price;
    }

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function isLowStock(): bool
    {
        return $this->physical_stock <= $this->min_stock;
    }
}
