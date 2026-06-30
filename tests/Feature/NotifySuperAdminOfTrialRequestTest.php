<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TrialRequestSubmitted;
use App\Listeners\NotifySuperAdminOfTrialRequest;
use App\Models\TrialRequest;
use App\Models\User;
use App\Notifications\NewTrialRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        User::factory()->superAdmin()->create();

        $this->post(route('trial.store'), [
            'name' => 'Carlos Díaz',
            'email' => 'carlos@taller.cl',
            'phone' => '+56922222222',
            'business_name' => 'Taller Carlos',
            'business_type' => 'Taller Mecánico',
            'terms' => true,
        ])->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', ['email' => 'carlos@taller.cl']);
    }
}
