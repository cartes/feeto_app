<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\PermissionRegistrar;
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
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            URL::defaults(['tenantBySlug' => $tenant->slug]);
        }

        $request->route()?->forgetParameter('tenantBySlug');

        return $next($request);
    }
}
