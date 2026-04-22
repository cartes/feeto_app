<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantPlan;
use App\Services\PlanFeatureService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'billing_api_key',
        'whatsapp_api_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'billing_api_key' => 'encrypted',
        'whatsapp_api_token' => 'encrypted',
        'subscription_ends_at' => 'datetime',
        'kanban_columns' => 'array',
        'max_discount_without_approval' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tenant): void {
            if (empty($tenant->slug)) {
                $tenant->slug = static::generateUniqueSlug($tenant->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function hasFeature(string $feature): bool
    {
        $tenant = $this->exists
            ? $this->fresh(['features', 'plan']) ?? $this
            : $this;

        $normalizedFeature = PlanFeatureService::normalizeFeatureKey($feature);
        $possibleFeatureKeys = PlanFeatureService::possibleFeatureKeys($feature);

        $override = $tenant->features()
            ->whereIn('feature', $possibleFeatureKeys)
            ->get()
            ->sortByDesc(fn (TenantFeature $tenantFeature): int => $tenantFeature->feature === $normalizedFeature ? 1 : 0)
            ->first();

        if ($override !== null) {
            return $override->is_enabled;
        }

        $resolvedPlan = $tenant->resolveTenantPlan();

        if ($resolvedPlan !== null) {
            return $resolvedPlan->includesFeature($normalizedFeature);
        }

        return $tenant->planModel()?->includesFeatureKey($normalizedFeature) ?? false;
    }

    public function currentPlan(): TenantPlan
    {
        return $this->resolveTenantPlan() ?? TenantPlan::GRATUITO;
    }

    public function userLimit(): int
    {
        $resolvedPlan = $this->resolveTenantPlan();

        if ($resolvedPlan !== null) {
            return $resolvedPlan->userLimit();
        }

        return (int) ($this->planModel()?->max_users ?? $this->getAttribute('max_users') ?? TenantPlan::GRATUITO->userLimit());
    }

    public function userLimitMessage(): string
    {
        $limit = $this->userLimit();
        $planName = $this->currentPlan()->label();

        return "El plan {$planName} permite un máximo de {$limit} usuarios. Actualiza tu plan para agregar más.";
    }

    /**
     * @return list<string>
     */
    public function enabledFeatureKeys(): array
    {
        $featureService = app(PlanFeatureService::class);

        return array_values(array_filter(
            $featureService->frontendFeatureKeys(),
            fn (string $feature): bool => $this->hasFeature($feature),
        ));
    }

    public function maxDiscountWithoutApproval(): float
    {
        return (float) ($this->getAttribute('max_discount_without_approval') ?? 10);
    }

    private function resolveTenantPlan(): ?TenantPlan
    {
        $storedPlan = $this->attributes['plan'] ?? null;

        if (is_string($storedPlan)) {
            $resolvedPlan = TenantPlan::fromStoredValue($storedPlan);

            if ($resolvedPlan !== null) {
                return $resolvedPlan;
            }
        }

        $resolvedFromPlanModel = $this->planModel()?->toTenantPlan();

        if ($resolvedFromPlanModel !== null) {
            return $resolvedFromPlanModel;
        }

        $storedPlanType = $this->attributes['plan_type'] ?? null;

        if (is_string($storedPlanType)) {
            $resolvedPlan = TenantPlan::fromStoredValue($storedPlanType);

            if ($resolvedPlan !== null) {
                return $resolvedPlan;
            }
        }

        return null;
    }

    private function planModel(): ?Plan
    {
        $planRelation = $this->relationLoaded('plan')
            ? $this->getRelation('plan')
            : $this->plan()->first();

        return $planRelation instanceof Plan ? $planRelation : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(TenantFeature::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TenantNote::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
