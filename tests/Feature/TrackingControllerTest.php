<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TrackingControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $this->setUpTenant();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
