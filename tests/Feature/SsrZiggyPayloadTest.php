<?php

namespace Tests\Feature;

use App\Support\Ssr\ZiggyInjectingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\HasHealthCheck;
use Inertia\Ssr\Response;
use Tests\TestCase;

class SsrZiggyPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_data_does_not_embed_ziggy_routes(): void
    {
        $this->withoutVite();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertArrayNotHasKey('routes', $response->viewData('page')['props']['ziggy']);
    }

    public function test_gateway_injects_ziggy_routes_for_the_ssr_server_only(): void
    {
        $inner = new class implements Gateway, HasHealthCheck
        {
            /** @var array<string, mixed> */
            public array $received = [];

            public function dispatch(array $page): ?Response
            {
                $this->received = $page;

                return null;
            }

            public function isHealthy(): bool
            {
                return true;
            }
        };

        $request = Request::create('/precios');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        (new ZiggyInjectingGateway($inner, $request))->dispatch(['props' => []]);

        $this->assertArrayHasKey('pricing', $inner->received['props']['ziggy']['routes']);
        $this->assertSame('http://localhost/precios', $inner->received['props']['ziggy']['location']);
    }
}
