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

        if (app()->runningUnitTests() && ! config('analytics.track_testing_visits', false)) {
            return $response;
        }

        if ($request->isMethod('GET') && ! $request->is('_*', 'api/*', 'admin/*') && $response->isSuccessful()) {
            $tenantId = Tenant::current()?->id;

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
}
