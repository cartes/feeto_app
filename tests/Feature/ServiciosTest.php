<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the services page renders successfully with Inertia.
     */
    public function test_servicios_page_renders_successfully(): void
    {
        $response = $this->get('/servicios');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Servicios')
                ->has('canLogin')
                ->has('seo')
                ->where('seo.title', 'Características y Módulos · TallerFlow — Sistema para Talleres')
                ->where('seo.description', 'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.')
            );
    }
}
