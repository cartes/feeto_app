<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TrialRequestSubmitted;
use App\Listeners\NotifySuperAdminOfTrialRequest;
use App\Models\TrialRequest;
use App\Models\User;
use App\Notifications\NewTrialRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifySuperAdminOfTrialRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_notification_to_super_admin_when_trial_request_is_submitted(): void
    {
        Notification::fake();
        Cache::flush();

        $superAdmin = User::factory()->superAdmin()->create();

        $trialRequest = TrialRequest::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@taller.cl',
            'phone' => '+56912345678',
            'business_name' => 'Taller El Mecánico',
            'business_type' => 'Taller Mecánico',
            'city' => 'Santiago',
            'status' => 'pending',
        ]);

        $event = new TrialRequestSubmitted($trialRequest);
        $listener = new NotifySuperAdminOfTrialRequest;
        $listener->handle($event);

        Notification::assertSentOnDemand(
            NewTrialRequestNotification::class,
            function (NewTrialRequestNotification $notification, array $channels, object $notifiable) use ($superAdmin, $trialRequest) {
                return in_array('mail', $channels)
                    && $notifiable->routes['mail'] === $superAdmin->email
                    && $notification->trialRequest->id === $trialRequest->id;
            }
        );
    }

    #[Test]
    public function it_does_nothing_when_no_super_admin_exists(): void
    {
        Notification::fake();
        Cache::flush();

        $trialRequest = TrialRequest::create([
            'name' => 'Ana López',
            'email' => 'ana@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Ana',
            'business_type' => 'Taller Mecánico',
            'status' => 'pending',
        ]);

        $event = new TrialRequestSubmitted($trialRequest);
        $listener = new NotifySuperAdminOfTrialRequest;
        $listener->handle($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_stores_trial_request_and_redirects_to_success(): void
    {
        Notification::fake();
        Cache::flush();

        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Carlos Díaz',
            'email' => 'carlos@taller.cl',
            'phone' => '+56922222222',
            'business_name' => 'Taller Carlos',
            'business_type' => 'Taller Mecánico',
            'terms' => true,
        ]);

        $response
            ->assertRedirect(route('trial.success'))
            ->assertSessionHas('trial_request_email', 'carlos@taller.cl');

        $this->assertDatabaseHas('trial_requests', ['email' => 'carlos@taller.cl']);

        Notification::assertSentOnDemandTimes(NewTrialRequestNotification::class, 1);
        Notification::assertSentOnDemand(
            NewTrialRequestNotification::class,
            function (NewTrialRequestNotification $notification, array $channels, object $notifiable) use ($superAdmin): bool {
                return in_array('mail', $channels, true)
                    && $notifiable->routes['mail'] === $superAdmin->email
                    && $notification->trialRequest->email === 'carlos@taller.cl';
            }
        );
    }

    #[Test]
    public function it_shows_the_submitted_email_on_the_success_page(): void
    {
        $response = $this->withSession([
            'trial_request_email' => 'equipo@taller.cl',
        ])->get(route('trial.success'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trial/Success')
            ->where('email', 'equipo@taller.cl')
            ->where('redirect_url', route('home'))
            ->where('redirect_delay_seconds', 6)
        );
    }

    #[Test]
    public function it_validates_the_email_when_submitting_a_trial_request(): void
    {
        Cache::flush();

        $response = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Carlos Díaz',
            'email' => 'correo-invalido',
            'phone' => '+56922222222',
            'business_name' => 'Taller Carlos',
            'business_type' => 'Taller Mecánico',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('trial_requests', [
            'email' => 'correo-invalido',
        ]);
    }

    #[Test]
    public function it_rejects_emails_without_a_top_level_domain_when_submitting_a_trial_request(): void
    {
        Cache::flush();

        $response = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Carlos Díaz',
            'email' => 'prueba@email',
            'phone' => '+56922222222',
            'business_name' => 'Taller Carlos',
            'business_type' => 'Taller Mecánico',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('trial_requests', [
            'email' => 'prueba@email',
        ]);
    }

    #[Test]
    public function it_sends_the_trial_request_notification_only_once_for_the_same_request(): void
    {
        Notification::fake();
        Cache::flush();

        $superAdmin = User::factory()->superAdmin()->create();

        $trialRequest = TrialRequest::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@taller.cl',
            'phone' => '+56912345678',
            'business_name' => 'Taller El Mecánico',
            'business_type' => 'Taller Mecánico',
            'city' => 'Santiago',
            'status' => 'pending',
        ]);

        $event = new TrialRequestSubmitted($trialRequest);
        $listener = new NotifySuperAdminOfTrialRequest;

        $listener->handle($event);
        $listener->handle($event);

        Notification::assertSentOnDemandTimes(NewTrialRequestNotification::class, 1);
        Notification::assertSentOnDemand(
            NewTrialRequestNotification::class,
            function (NewTrialRequestNotification $notification, array $channels, object $notifiable) use ($superAdmin, $trialRequest): bool {
                return in_array('mail', $channels, true)
                    && $notifiable->routes['mail'] === $superAdmin->email
                    && $notification->trialRequest->id === $trialRequest->id;
            }
        );
    }
}
