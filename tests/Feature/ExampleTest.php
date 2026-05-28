<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ExampleTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->setUpTenant();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister'));
    }

    public function test_authenticated_user_can_visit_homepage_without_redirection(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->where('auth.user.id', $user->id));
    }
}
