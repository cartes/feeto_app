<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class RegistrationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('taller.dashboard', ['tenantBySlug' => 'taller-test']));
    }

    public function test_registration_is_blocked_when_tenant_user_limit_is_reached(): void
    {
        $tenant = Tenant::current();

        User::factory()->create(['tenant_id' => $tenant->id]);
        User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Usuario Extra',
            'email' => 'extra@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
