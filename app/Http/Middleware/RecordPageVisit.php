<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PageVisitRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPageVisit
{
    public function __construct(
        private readonly PageVisitRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->runningUnitTests() && ! config('analytics.track_testing_visits', false)) {
            return $response;
        }

        $this->recorder->record($request, $response);

        return $response;
    }
}
