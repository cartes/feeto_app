<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantPlan;
use App\Services\PlanFeatureService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    /** @var array<string, bool>|null */
    protected ?array $resolvedFeatureAvailability = null;

    protected ?TenantPlan $resolvedTenantPlan = null;

    protected bool $tenantPlanResolved = false;

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
        'scheduling_config' => 'array',
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

    public function setAttribute($key, $value): static
    {
        if (in_array((string) $key, ['plan', 'plan_id', 'plan_type'], true)) {
            $this->resetResolvedPlanContext();
        }

        return parent::setAttribute($key, $value);
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
        return $this->featureAvailabilityMap([$feature])[PlanFeatureService::normalizeFeatureKey($feature)] ?? false;
    }

    public function currentPlan(): TenantPlan
    {
        if (! $this->tenantPlanResolved) {
            $this->resolvedTenantPlan = $this->resolveTenantPlan();
            $this->tenantPlanResolved = true;
        }

        return $this->resolvedTenantPlan ?? TenantPlan::GRATUITO;
    }

    public function userLimit(): int
    {
        $resolvedPlan = $this->currentPlan();

        return (int) ($this->planModel()?->max_users ?? $this->getAttribute('max_users') ?? $resolvedPlan->userLimit());
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
        $frontendFeatureKeys = $featureService->frontendFeatureKeys();
        $featureAvailability = $this->featureAvailabilityMap($frontendFeatureKeys);

        return array_values(array_filter(
            $frontendFeatureKeys,
            static fn (string $feature): bool => $featureAvailability[$feature] ?? false,
        ));
    }

    /**
     * @param  list<string>|null  $features
     * @return array<string, bool>
     */
    public function featureAvailabilityMap(?array $features = null): array
    {
        $requestedFeatures = $features ?? app(PlanFeatureService::class)->allFeatureKeys();

        $this->loadFeatureContext();

        if ($this->resolvedFeatureAvailability === null) {
            $resolvedPlan = $this->currentPlan();
            $overrides = $this->featureOverrides();

            $this->resolvedFeatureAvailability = collect(app(PlanFeatureService::class)->allFeatureKeys())
                ->mapWithKeys(static function (string $feature) use ($overrides, $resolvedPlan): array {
                    $normalizedFeature = PlanFeatureService::normalizeFeatureKey($feature);

                    return [
                        $normalizedFeature => $overrides[$normalizedFeature] ?? $resolvedPlan->includesFeature($normalizedFeature),
                    ];
                })
                ->all();
        }

        return collect($requestedFeatures)
            ->mapWithKeys(function (string $feature): array {
                $normalizedFeature = PlanFeatureService::normalizeFeatureKey($feature);

                return [$normalizedFeature => $this->resolvedFeatureAvailability[$normalizedFeature] ?? false];
            })
            ->all();
    }

    public function logoUrl(): ?string
    {
        $path = $this->getAttribute('logo_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function maxDiscountWithoutApproval(): float
    {
        return (float) ($this->getAttribute('max_discount_without_approval') ?? 10);
    }

    /** @return array<string, mixed> */
    public function schedulingConfig(): array
    {
        return $this->getAttribute('scheduling_config') ?? self::defaultSchedulingConfig();
    }

    /** @return array<string, mixed> */
    public static function defaultSchedulingConfig(): array
    {
        $defaultDay = ['enabled' => true, 'open' => '09:00', 'close' => '18:00'];
        $closedDay = ['enabled' => false, 'open' => '09:00', 'close' => '14:00'];

        return [
            'slot_duration' => 60,
            'days' => [
                'monday' => $defaultDay,
                'tuesday' => $defaultDay,
                'wednesday' => $defaultDay,
                'thursday' => $defaultDay,
                'friday' => $defaultDay,
                'saturday' => $closedDay,
                'sunday' => $closedDay,
            ],
            'blocked_slots' => [],
            'blocked_dates' => [],
        ];
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
        if ($this->exists) {
            $this->loadMissing('plan');
        }

        $planRelation = $this->getRelation('plan');

        return $planRelation instanceof Plan ? $planRelation : null;
    }

    private function loadFeatureContext(): void
    {
        if (! $this->exists) {
            return;
        }

        $this->loadMissing(['features', 'plan']);
    }

    /**
     * @return array<string, bool>
     */
    private function featureOverrides(): array
    {
        $this->loadFeatureContext();

        /** @var array<string, array{priority: int, is_enabled: bool}> $overrides */
        $overrides = [];

        /** @var TenantFeature $feature */
        foreach ($this->features as $feature) {
            $normalizedFeature = PlanFeatureService::normalizeFeatureKey($feature->feature);
            $priority = $feature->feature === $normalizedFeature ? 1 : 0;

            if (! isset($overrides[$normalizedFeature]) || $priority > $overrides[$normalizedFeature]['priority']) {
                $overrides[$normalizedFeature] = [
                    'priority' => $priority,
                    'is_enabled' => $feature->is_enabled,
                ];
            }
        }

        return collect($overrides)
            ->mapWithKeys(static fn (array $override, string $feature): array => [$feature => $override['is_enabled']])
            ->all();
    }

    private function resetResolvedPlanContext(): void
    {
        $this->resolvedFeatureAvailability = null;
        $this->resolvedTenantPlan = null;
        $this->tenantPlanResolved = false;
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
