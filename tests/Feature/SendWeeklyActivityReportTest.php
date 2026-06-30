<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendWeeklyActivityReport;
use App\Models\TrialRequest;
use App\Models\User;
use App\Notifications\WeeklyActivityReportNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendWeeklyActivityReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_weekly_report_to_super_admin(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->superAdmin()->create();

        (new SendWeeklyActivityReport)->handle();

        Notification::assertSentOnDemand(
            WeeklyActivityReportNotification::class,
            function (WeeklyActivityReportNotification $notification, array $channels, object $notifiable) use ($superAdmin) {
                return in_array('mail', $channels)
                    && $notifiable->routes['mail'] === $superAdmin->email;
            }
        );
    }

    #[Test]
    public function it_does_nothing_when_no_super_admin_exists(): void
    {
        Notification::fake();

        (new SendWeeklyActivityReport)->handle();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_includes_correct_metrics_in_notification(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();

        TrialRequest::create([
            'name' => 'Taller Test',
            'email' => 'taller@test.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Test S.A.',
            'business_type' => 'Taller Mecánico',
            'status' => 'pending',
        ]);

        (new SendWeeklyActivityReport)->handle();

        Notification::assertSentOnDemand(
            WeeklyActivityReportNotification::class,
            function (WeeklyActivityReportNotification $notification) {
                return $notification->metrics['pending_trial_requests'] === 1
                    && isset($notification->metrics['total_active_tenants'])
                    && isset($notification->metrics['new_work_orders'])
                    && isset($notification->metrics['week_start'])
                    && isset($notification->metrics['week_end']);
            }
        );
    }

    #[Test]
    public function it_is_scheduled_weekly_on_saturdays_at_noon(): void
    {
        $schedule = app(Schedule::class);

        $matchingEvents = collect($schedule->events())->filter(
            fn ($event) => str_contains((string) ($event->description ?? ''), 'SendWeeklyActivityReport')
        );

        $this->assertCount(1, $matchingEvents);
        $this->assertEquals('0 12 * * 6', $matchingEvents->first()->expression);
    }
}
