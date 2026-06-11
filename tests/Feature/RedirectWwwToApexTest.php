<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectWwwToApexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_www_host_redirects_to_apex_with_status_301(): void
    {
        $response = $this->get('http://www.tallerflow.cl/blog/algun-post');

        $response->assertStatus(301);
        $response->assertRedirect('https://tallerflow.cl/blog/algun-post');
    }

    public function test_www_host_redirect_preserves_query_string(): void
    {
        $response = $this->get('http://www.tallerflow.cl/blog?page=2');

        $response->assertStatus(301);
        $response->assertRedirect('https://tallerflow.cl/blog?page=2');
    }

    public function test_apex_host_is_not_redirected(): void
    {
        $response = $this->get('http://tallerflow.cl/');

        $response->assertOk();
    }
}
