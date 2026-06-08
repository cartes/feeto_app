<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_super_admin_when_missing(): void
    {
        $this->artisan('feeto:ensure-super-admin', [
            'email' => 'superadmin@tallerflow.cl',
            '--password' => 'ClaveSegura123!',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'superadmin@tallerflow.cl')->first();

        $this->assertNotNull($user);
        $this->assertSame('Super Admin', $user->name);
        $this->assertTrue($user->is_super_admin);
        $this->assertNull($user->tenant_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('ClaveSegura123!', $user->password));
    }

    public function test_it_promotes_an_existing_user_and_updates_password_when_requested(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'name' => 'Usuario Normal',
            'email' => 'admin@tallerflow.cl',
            'password' => 'password-anterior',
            'is_super_admin' => false,
            'tenant_id' => $tenant->id,
            'email_verified_at' => null,
        ]);

        $this->artisan('feeto:ensure-super-admin', [
            'email' => 'admin@tallerflow.cl',
            '--name' => 'Super Admin Recuperado',
            '--password' => 'NuevaClaveSegura123!',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertSame('Super Admin Recuperado', $user->name);
        $this->assertTrue($user->is_super_admin);
        $this->assertNull($user->tenant_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NuevaClaveSegura123!', $user->password));
    }
}
