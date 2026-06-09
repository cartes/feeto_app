<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->tenant = Tenant::factory()->create([
            'name' => 'Taller Activo',
            'domain' => 'activo.tallerflow.test',
        ]);
    }

    public function test_superadmin_can_access_tenant_activity_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.activity', $this->tenant));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Activity')
            ->where('tenant.id', $this->tenant->id)
            ->where('tenant.name', 'Taller Activo')
        );
    }

    public function test_non_superadmin_cannot_access_tenant_activity_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.tenants.activity', $this->tenant));

        $response->assertForbidden();
    }
}
