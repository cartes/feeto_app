<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardGracefulPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_fail_when_financial_permission_is_missing_from_tenant_setup(): void
    {
        $tenant = Tenant::factory()->create([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)->get(route('taller.dashboard', [
            'tenantBySlug' => $tenant->slug,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('overdueInvoices', [])
            );
    }
}
