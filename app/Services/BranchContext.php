<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchContext
{
    protected ?Branch $resolvedBranch = null;

    protected bool $resolved = false;

    public function __construct(protected ?Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * Resuelve la sucursal activa actual para la petición.
     */
    public function current(): ?Branch
    {
        if ($this->resolved) {
            return $this->resolvedBranch;
        }

        $this->resolved = true;
        $tenant = Tenant::current();

        if (! $tenant) {
            return $this->resolvedBranch = null;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->resolvedBranch = null;
        }

        // Si el usuario tiene una sucursal fijada, esa es siempre su sucursal activa
        if ($user->branch_id !== null) {
            return $this->resolvedBranch = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->find($user->branch_id);
        }

        // Si es Super Admin del Tenant, verificar si tiene una sucursal seleccionada en sesión
        $sessionBranchId = $this->request?->hasSession() ? $this->request->session()->get('active_branch_id') : null;

        if ($sessionBranchId && $sessionBranchId !== 'all') {
            $branch = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->find((int) $sessionBranchId);

            if ($branch) {
                return $this->resolvedBranch = $branch;
            }

            // Si la sucursal ya no existe o no pertenece al tenant, limpiar sesión
            if ($this->request?->hasSession()) {
                $this->request->session()->forget('active_branch_id');
            }
        }

        return $this->resolvedBranch = null;
    }

    /**
     * Obtiene el ID de la sucursal activa actual, o null si está en modo consolidado / todas.
     */
    public function id(): ?int
    {
        return $this->current()?->id;
    }

    /**
     * Determina si el contexto actual abarca todas las sucursales del taller (modo consolidado).
     */
    public function isAll(): bool
    {
        return $this->current() === null;
    }

    /**
     * Determina si el usuario está fijado estrictamente a una sucursal y no puede cambiar.
     */
    public function isFixed(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->branch_id !== null;
    }

    /**
     * Cambia la sucursal activa en la sesión del usuario (solo permitido a Super Admins del Tenant).
     */
    public function switchBranch(?int $branchId): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || $this->isFixed()) {
            return false;
        }

        $tenant = Tenant::current();

        if (! $tenant) {
            return false;
        }

        if (! $this->request?->hasSession()) {
            return false;
        }

        if ($branchId === null || $branchId === 0) {
            $this->request->session()->put('active_branch_id', 'all');
            $this->resolved = false;

            return true;
        }

        $branchExists = Branch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('id', $branchId)
            ->exists();

        if (! $branchExists) {
            return false;
        }

        $this->request->session()->put('active_branch_id', $branchId);
        $this->resolved = false;

        return true;
    }

    /**
     * Retorna todas las sucursales activas del taller actual.
     *
     * @return Collection<int, Branch>
     */
    public function availableBranches(): Collection
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return new Collection;
        }

        return Branch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Serializa los datos del contexto para ser consumidos por Inertia y Vue.
     *
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $current = $this->current();
        $isFixed = $this->isFixed();

        return [
            'current' => $current ? [
                'id' => $current->id,
                'name' => $current->name,
                'code' => $current->code,
                'is_main' => $current->is_main,
                'address' => $current->address,
                'phone' => $current->phone,
            ] : null,
            'is_all' => $this->isAll(),
            'is_fixed' => $isFixed,
            'available' => $this->availableBranches()->map(fn (Branch $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'is_main' => $b->is_main,
            ])->values()->all(),
        ];
    }
}
