<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_super_admin', 'tenant_id', 'branch_id', 'needs_password_change', 'onboarding_tour_completed_at', 'onboarding_sections_completed'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'needs_password_change' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'needs_password_change' => 'boolean',
            'onboarding_tour_completed_at' => 'datetime',
            'onboarding_sections_completed' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obtiene la sucursal asignada al usuario (sub-tenant).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Determina si el usuario es un Super Admin del Taller (Tenant Super Admin).
     * Tiene acceso transversal a todas las sucursales del taller.
     */
    public function isTenantSuperAdmin(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->tenant_id !== null
            && $this->branch_id === null
            && $this->hasRole('Admin');
    }

    /**
     * Determina si el usuario está restringido a una sucursal específica.
     */
    public function isBranchUser(): bool
    {
        return $this->branch_id !== null;
    }

    /**
     * Verifica si el usuario tiene permiso para acceder o gestionar la sucursal dada.
     */
    public function canAccessBranch(?int $branchId): bool
    {
        if ($branchId === null) {
            return $this->isTenantSuperAdmin();
        }

        if ($this->isTenantSuperAdmin()) {
            return true;
        }

        return $this->branch_id === $branchId;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
