<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_renders_with_sqlite_database(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Tenant::factory()->create([
            'created_at' => now()->subMonths(2),
        ]);

        Tenant::factory()->create([
            'created_at' => now()->subMonth(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('new_tenants_by_month')
                ->has('stats')
            );
    }
}
