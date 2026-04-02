<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'rut_taller',
        'is_active',
        'plan_type',
        'max_users',
        'billing_api_key',
        'whatsapp_api_token',
        'plan',
        'status',
        'subscription_ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'billing_api_key' => 'encrypted',
        'whatsapp_api_token' => 'encrypted',
        'subscription_ends_at' => 'datetime',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }
}
