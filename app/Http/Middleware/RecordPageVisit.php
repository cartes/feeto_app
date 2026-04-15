<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class RecordPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->is('_*', 'api/*', 'admin/*') && $response->isSuccessful()) {
            $tenantId = Tenant::current()?->id;
            PageVisit::record($request->path() ?: '/', $tenantId);
        }

        return $response;
    }
}
