<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenDeTrabajoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the static SEO page for workshop work orders renders successfully.
     */
    public function test_orden_de_trabajo_page_renders_successfully(): void
    {
        $response = $this->get('/orden-de-trabajo-taller-mecanico');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/OrdenDeTrabajo')
            );
    }
}
