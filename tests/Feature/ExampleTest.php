<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ExampleTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->setUpTenant();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
