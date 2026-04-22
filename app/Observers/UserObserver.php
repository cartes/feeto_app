<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    /**
     * @throws ValidationException
     */
    public function creating(User $user): void
    {
        if ($user->is_super_admin || ! $user->tenant_id) {
            return;
        }

        $tenant = $user->relationLoaded('tenant')
            ? $user->tenant
            : $user->tenant()->first();

        if ($tenant === null) {
            return;
        }

        if ($tenant->users()->count() >= $tenant->userLimit()) {
            throw ValidationException::withMessages([
                'email' => $tenant->userLimitMessage(),
            ]);
        }
    }
}
