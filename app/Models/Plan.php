<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'description', 'price_monthly', 'price_annual',
    'features', 'max_users', 'max_branches', 'is_active', 'is_popular', 'trial_days',
    'discount_percent', 'discount_valid_until', 'sort_order',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'discount_valid_until' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $plan): void {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasActiveDiscount(): bool
    {
        return $this->discount_percent > 0
            && ($this->discount_valid_until === null || $this->discount_valid_until->isFuture());
    }

    public function discountedMonthlyPrice(): int
    {
        if (! $this->hasActiveDiscount()) {
            return $this->price_monthly;
        }

        return (int) round($this->price_monthly * (1 - $this->discount_percent / 100));
    }
}
