<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'description',
        'cost_price',
        'selling_price',
        'tax_included',
        'estimated_minutes',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_included' => 'boolean',
        'estimated_minutes' => 'integer',
        'is_active' => 'boolean',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
