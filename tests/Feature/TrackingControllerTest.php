<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

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
