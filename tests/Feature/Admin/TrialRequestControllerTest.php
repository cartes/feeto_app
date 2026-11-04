<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Notifications\ClientTrialRequestReceivedNotification;
use App\Notifications\TrialRequestApprovedNotification;
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

    public function test_submitting_trial_request_sends_notification_with_tutorial_and_booking_link_to_workshop(): void
    {
        Notification::fake();

        $response = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Juan Perez',
            'email' => 'juan@tallersur.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Sur',
            'business_type' => 'Mecanica general',
            'city' => 'Santiago',
            'users_estimate' => 3,
            'requested_plan' => 'basico',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('trial.success'));

        $expectedBookingUrl = route('taller.landing', ['tenantBySlug' => 'taller-sur']);

        Notification::assertSentOnDemand(
            ClientTrialRequestReceivedNotification::class,
            function (ClientTrialRequestReceivedNotification $notification, array $channels, object $notifiable) use ($expectedBookingUrl): bool {
                $mail = $notification->toMail($notifiable);
                $rendered = (string) $mail->render();

                $this->assertStringContainsString('Pequeño tutorial', $rendered);
                $this->assertStringContainsString('Recepción inteligente', $rendered);
                $this->assertStringContainsString('Órdenes de Trabajo (OT) y Tablero Kanban', $rendered);
                $this->assertStringContainsString($expectedBookingUrl, $rendered);

                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === 'juan@tallersur.cl';
            }
        );
    }

    public function test_approving_trial_request_sends_notification_with_tutorial_and_booking_link_to_tenant_admin(): void
    {
        Notification::fake();

        $trialRequest = TrialRequest::create([
            'name' => 'Pedro Rojas',
            'email' => 'pedro@tallernorte.cl',
            'phone' => '+56922222222',
            'business_name' => 'Taller Norte',
            'business_type' => 'Mecanica general',
            'city' => 'Concepción',
            'country' => 'CL',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.trial-requests.approve', $trialRequest), [
                'admin_password' => 'TempPass1234',
            ]);

        $response->assertRedirect();

        $trialRequest->refresh();
        $tenant = Tenant::findOrFail($trialRequest->tenant_id);
        $admin = User::where('tenant_id', $tenant->id)->firstOrFail();
        $expectedBookingUrl = route('taller.landing', ['tenantBySlug' => $tenant->slug]);

        Notification::assertSentTo(
            $admin,
            TrialRequestApprovedNotification::class,
            function (TrialRequestApprovedNotification $notification) use ($admin, $expectedBookingUrl): bool {
                $mail = $notification->toMail($admin);
                $rendered = (string) $mail->render();

                $this->assertStringContainsString('Pequeño tutorial', $rendered);
                $this->assertStringContainsString('Agendamiento en línea para tus clientes', $rendered);
                $this->assertStringContainsString('Recepción inteligente de vehículos', $rendered);
                $this->assertStringContainsString('Órdenes de Trabajo (OT) y Tablero Kanban', $rendered);
                $this->assertStringContainsString($expectedBookingUrl, $rendered);
                $this->assertStringContainsString('TempPass1234', $rendered);

                return true;
            }
        );
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
            'country' => 'CL',
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
