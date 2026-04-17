<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class BranchLimitService
{
    /**
     * Check if a tenant can create a new branch based on their plan limits.
     */
    public function canCreateBranch(Tenant $tenant): bool
    {
        $maxBranches = $this->getMaxBranchesForTenant($tenant);

        // Unlimited branches (0 means unlimited)
        if ($maxBranches === 0) {
            return true;
        }

        $currentBranches = $tenant->branches()->where('is_active', true)->count();

        return $currentBranches < $maxBranches;
    }

    /**
     * Get the maximum number of branches allowed for a tenant.
     *
     * @return int 0 means unlimited
     */
    public function getMaxBranchesForTenant(Tenant $tenant): int
    {
        $plan = $tenant->plan;

        if ($plan === null) {
            return 1; // Default to 1 branch if no plan
        }

        return (int) ($plan->features['max_branches'] ?? $plan->max_branches ?? 1);
    }

    /**
     * Get the remaining branches allowed for a tenant.
     *
     * @return int -1 means unlimited
     */
    public function getRemainingBranches(Tenant $tenant): int
    {
        $maxBranches = $this->getMaxBranchesForTenant($tenant);

        if ($maxBranches === 0) {
            return -1; // Unlimited
        }

        $currentBranches = $tenant->branches()->where('is_active', true)->count();

        return max(0, $maxBranches - $currentBranches);
    }

    /**
     * Get a human-readable limit message.
     */
    public function getLimitMessage(Tenant $tenant): string
    {
        $maxBranches = $this->getMaxBranchesForTenant($tenant);
        $currentBranches = $tenant->branches()->where('is_active', true)->count();

        if ($maxBranches === 0) {
            return 'Sucursales ilimitadas.';
        }

        $remaining = max(0, $maxBranches - $currentBranches);

        return "{$currentBranches} de {$maxBranches} sucursales utilizadas. {$remaining} disponibles.";
    }
}
