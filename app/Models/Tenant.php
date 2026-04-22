<?php

declare(strict_types=1);

namespace App\Models;

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
        $tenant = static::query()->find($this->getKey());

        if (! $tenant) {
            return false;
        }

        $override = $tenant->features()
            ->where('feature', $feature)
            ->first();

        if ($override !== null) {
            return $override->is_enabled;
        }

        return $tenant->plan()->first()?->includesFeatureKey($feature) ?? false;
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
