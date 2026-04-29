<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Combina aislamiento por tenant con borrado lógico (soft delete).
 * Usar en modelos que pertenecen a un tenant y requieren auditoría de eliminación.
 */
trait BelongsToTenantAndSoftDeletes
{
    use TenantAware;
    use SoftDeletes;
}
