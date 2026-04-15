<?php

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

class PathOrDomainTenantFinder extends DomainTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        if ($request->segment(1) === 'taller' && filled($request->segment(2))) {
            return app(IsTenant::class)::query()
                ->where('slug', $request->segment(2))
                ->first();
        }

        return parent::findForRequest($request);
    }
}
