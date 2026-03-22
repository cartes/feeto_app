<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Spatie\Multitenancy\Models\Tenant;

trait TenantAware
{
    protected static function bootTenantAware(): void
    {
        static::addGlobalScope('tenant', new class implements Scope {
            public function apply(Builder $builder, Model $model): void
            {
                if (Tenant::checkCurrent()) {
                    $builder->where('tenant_id', Tenant::current()->id);
                }
            }
        });

        static::creating(function (Model $model): void {
            if (Tenant::checkCurrent() && !isset($model->tenant_id)) {
                $model->tenant_id = Tenant::current()->id;
            }
        });
    }
}
