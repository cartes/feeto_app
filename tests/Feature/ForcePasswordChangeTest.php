<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ForcePasswordChangeTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_newly_created_user_defaults_to_needing_password_change(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($user->needs_password_change);
    }

    public function test_self_registered_user_does_not_need_password_change(): void
    {
        $tenant = $this->setUpTenant();

        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@tallertest.cl',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'john@tallertest.cl')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->needs_password_change);
    }

    public function test_guest_cannot_access_force_password_change_route(): void
    {
        $response = $this->post(route('password.force-change'), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_force_change_password_with_valid_credentials(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'needs_password_change' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('password.force-change'), [
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->needs_password_change);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
    }

    public function test_force_change_password_validates_required_fields(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'needs_password_change' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('password.force-change'), [
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response->assertSessionHasErrors(['password']);
        $user->refresh();
        $this->assertTrue($user->needs_password_change);
    }
}
