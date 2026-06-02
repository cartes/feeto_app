<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\Header;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class DashboardRedirectTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_super_admin_inertia_dashboard_visit_forces_a_location_redirect_to_admin_dashboard(): void
    {
        $user = User::factory()->superAdmin()->create();

        $inertiaVersion = app(HandleInertiaRequests::class)->version(request());

        $response = $this->actingAs($user)->get(route('dashboard', absolute: false), [
            Header::INERTIA => 'true',
            Header::VERSION => $inertiaVersion,
        ]);

        $response
            ->assertStatus(409)
            ->assertHeader(Header::LOCATION, route('admin.dashboard'));
    }

    public function test_super_admin_standard_dashboard_visit_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->get(route('dashboard', absolute: false));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_tenant_user_dashboard_visit_redirects_to_their_tenant_dashboard(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', absolute: false));

        $response->assertRedirect(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]));
    }
}
