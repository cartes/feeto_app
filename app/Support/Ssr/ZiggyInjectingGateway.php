<?php

namespace App\Support\Ssr;

use App\Support\ZiggyRouteGroup;
use Illuminate\Http\Request;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\HasHealthCheck;
use Inertia\Ssr\Response;
use Tighten\Ziggy\Ziggy;

/**
 * Entrega las rutas de Ziggy solo al servidor SSR, para no repetirlas en `data-page`
 * (el navegador ya las recibe una vez vía `@routes`).
 */
class ZiggyInjectingGateway implements Gateway, HasHealthCheck
{
    public function __construct(
        private readonly Gateway&HasHealthCheck $inner,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $page
     */
    public function dispatch(array $page): ?Response
    {
        $page['props']['ziggy'] = [
            ...(new Ziggy(ZiggyRouteGroup::forRequest($this->request)))->toArray(),
            'location' => $this->request->url(),
        ];

        return $this->inner->dispatch($page);
    }

    public function isHealthy(): bool
    {
        return $this->inner->isHealthy();
    }
}
