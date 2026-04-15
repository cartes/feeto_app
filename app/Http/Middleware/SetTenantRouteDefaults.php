<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetTenantRouteDefaults
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeTenant = $request->route('tenantBySlug');

        if ($routeTenant instanceof Tenant) {
            $routeTenant->makeCurrent();
        }

        $tenant = Tenant::current();

        if ($tenant && $tenant->slug) {
            URL::defaults(['tenantBySlug' => $tenant->slug]);
        }

        $request->route()?->forgetParameter('tenantBySlug');

        return $next($request);
    }
}
