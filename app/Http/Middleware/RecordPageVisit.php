<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PageVisit;
use App\Models\Tenant as AppTenant;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant as CurrentTenant;
use Symfony\Component\HttpFoundation\Response;

class RecordPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->runningUnitTests() && ! config('analytics.track_testing_visits', false)) {
            return $response;
        }

        if ($request->isMethod('GET') && ! $request->is('_*', 'api/*', 'admin/*') && $response->isSuccessful()) {
            $tenantId = $this->resolveTenantId($request);

            $isUnique = false;
            if ($request->hasSession()) {
                $today = now()->toDateString();
                $path = $request->path() ?: '/';
                $sessionKey = "visited_paths.{$today}";
                $visitedPaths = $request->session()->get($sessionKey, []);

                if (! in_array($path, $visitedPaths, true)) {
                    $visitedPaths[] = $path;
                    $request->session()->put($sessionKey, $visitedPaths);
                    $isUnique = true;
                }
            }

            PageVisit::record($request->path() ?: '/', $tenantId, $isUnique);
        }

        return $response;
    }

    private function resolveTenantId(Request $request): ?int
    {
        if (CurrentTenant::checkCurrent()) {
            return CurrentTenant::current()?->id;
        }

        $routeTenant = $request->route('tenantBySlug');

        if ($routeTenant instanceof AppTenant) {
            return $routeTenant->id;
        }

        if (is_string($routeTenant) && $routeTenant !== '') {
            return AppTenant::query()
                ->where('slug', $routeTenant)
                ->value('id');
        }

        return null;
    }
}
