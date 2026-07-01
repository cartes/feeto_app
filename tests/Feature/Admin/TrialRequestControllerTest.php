<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\TrialRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrialRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_super_admin_can_delete_rejected_trial_request_and_free_email_for_a_new_request(): void
    {
        Notification::fake();

        $trialRequest = TrialRequest::create([
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'phone' => '+56911111111',
            'business_name' => 'Taller Perez',
            'business_type' => 'Mecanica general',
            'city' => 'Santiago',
            'users_estimate' => 3,
            'requested_plan' => 'basico',
            'message' => 'Primera solicitud',
            'status' => 'rejected',
            'rejection_reason' => 'Faltaban antecedentes',
        ]);

        $deleteResponse = $this->actingAs($this->superAdmin)
            ->followingRedirects()
            ->from(route('admin.trial-requests.index', ['status' => 'rejected']))
            ->delete(route('admin.trial-requests.destroy', $trialRequest));

        $deleteResponse->assertOk();
        $deleteResponse->assertInertia(fn ($page) => $page
            ->component('Admin/TrialRequests/Index')
            ->where('flash.success', 'Solicitud eliminada definitivamente. El correo queda libre para una nueva solicitud.')
            ->where('current_status', 'rejected')
        );

        $this->assertDatabaseMissing('trial_requests', [
            'id' => $trialRequest->id,
        ]);

        $storeResponse = $this->post(route('trial.store'), [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'phone' => '+56911111111',
            'business_name' => 'Taller Perez',
            'business_type' => 'Mecanica general',
            'city' => 'Santiago',
            'users_estimate' => 3,
            'requested_plan' => 'basico',
            'message' => 'Segunda solicitud',
            'terms' => '1',
        ]);

        $storeResponse->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', [
            'email' => 'juan@example.com',
            'status' => 'pending',
            'message' => 'Segunda solicitud',
        ]);
    }
}
