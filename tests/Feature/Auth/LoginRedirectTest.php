<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\SetTenantRouteDefaults;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * Prueba la lógica de redirección post-login según el rol del usuario:
 * - Superadmin → /admin
 * - Usuario con tenant → /taller/{slug}/dashboard
 * - Usuario sin tenant → /dashboard (fallback)
 */
class LoginRedirectTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->setUpTenant();
    }

    public function test_super_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_tenant_user_is_redirected_to_taller_dashboard(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]));
    }

    public function test_user_without_tenant_is_redirected_to_generic_dashboard(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_shortcut_redirects_tenant_user_to_tenant_dashboard(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]));
    }

    public function test_tenant_route_defaults_include_current_tenant_slug(): void
    {
        $this->tenant->makeCurrent();

        app(SetTenantRouteDefaults::class)->handle(Request::create('/'), function (): Response {
            $this->assertSame(
                "/taller/{$this->tenant->slug}/work-orders",
                route('work-orders.index', absolute: false)
            );

            return response()->noContent();
        });
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_taller_dashboard_requires_authentication(): void
    {
        $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_access_another_tenant_dashboard(): void
    {
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($user)
            ->get(route('taller.dashboard', ['tenantBySlug' => $otherTenant->slug]))
            ->assertForbidden();
    }
}
