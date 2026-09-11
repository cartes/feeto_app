<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendDailyActivityReport;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DailyActivityReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TestDailyReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_trigger_test_daily_report(): void
    {
        Notification::fake();

        config(['mail.admin_report_email' => 'contacto@tallerflow.cl']);

        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.test-daily-report'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentOnDemand(
            DailyActivityReportNotification::class,
            function (DailyActivityReportNotification $notification, array $channels, object $notifiable): bool {
                return in_array('mail', $channels, true)
                    && $notifiable->routes['mail'] === 'contacto@tallerflow.cl'
                    && ($notification->metrics['is_test'] ?? false) === true;
            }
        );
    }

    public function test_regular_user_cannot_trigger_test_daily_report(): void
    {
        Notification::fake();

        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)
            ->post(route('admin.test-daily-report'));

        $response->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_send_daily_activity_report_job_collects_metrics(): void
    {
        Notification::fake();

        Tenant::factory()->create(['is_active' => true]);

        $job = new SendDailyActivityReport(targetEmail: 'contacto@tallerflow.cl', isTest: true);
        $metrics = $job->handle();

        $this->assertArrayHasKey('total_active_tenants', $metrics);
        $this->assertGreaterThanOrEqual(1, $metrics['total_active_tenants']);
        $this->assertTrue($metrics['is_test']);

        Notification::assertSentOnDemand(
            DailyActivityReportNotification::class,
            fn (DailyActivityReportNotification $n) => $n->metrics['report_date'] === now()->format('d/m/Y')
        );
    }
}
