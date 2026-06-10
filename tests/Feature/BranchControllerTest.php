<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BranchControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->setUpTenant();

        $this->admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_create_a_branch(): void
    {
        $this->actingAs($this->admin)
            ->post(route('branches.store'), [
                'name' => 'Sucursal Centro',
                'code' => 'CTR',
                'address' => 'Av. Siempre Viva 123',
                'phone' => '+56912345678',
                'email' => 'centro@taller.cl',
                'is_main' => true,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', [
            'name' => 'Sucursal Centro',
            'code' => 'CTR',
            'is_main' => true,
        ]);
    }

    public function test_admin_can_update_a_branch(): void
    {
        $branch = Branch::factory()->create(['code' => 'SUR']);

        $this->actingAs($this->admin)
            ->from(route('branches.index'))
            ->put(route('branches.update', ['branch' => $branch->id]), [
                'name' => 'Sucursal Sur Actualizada',
                'code' => 'SUR',
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'is_active' => true,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Sucursal Sur Actualizada',
        ]);
    }

    public function test_creating_a_branch_with_a_duplicate_code_fails_validation(): void
    {
        Branch::factory()->create(['code' => 'NOR']);

        $this->actingAs($this->admin)
            ->from(route('branches.index'))
            ->post(route('branches.store'), [
                'name' => 'Sucursal Norte 2',
                'code' => 'NOR',
            ])
            ->assertSessionHasErrors('code');
    }
}
