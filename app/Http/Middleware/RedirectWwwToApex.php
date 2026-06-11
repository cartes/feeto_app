<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normaliza el dominio del marketing site a "tallerflow.cl" (sin www) para
 * evitar que Google indexe el contenido bajo dos hosts distintos y se
 * fragmente la autoridad del dominio.
 */
class RedirectWwwToApex
{
    private const WWW_HOST = 'www.tallerflow.cl';

    private const APEX_HOST = 'tallerflow.cl';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === self::WWW_HOST) {
            return redirect()->away(
                'https://'.self::APEX_HOST.$request->getRequestUri(),
                301
            );
        }

        return $next($request);
    }
}
