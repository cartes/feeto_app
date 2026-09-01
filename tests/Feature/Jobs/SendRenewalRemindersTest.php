<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendRenewalReminders;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionRenewalReminder;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SendRenewalRemindersTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_sends_notification_to_admin_when_subscription_expires_within_7_days(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->addDays(3), 'is_active' => true]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        (new SendRenewalReminders)->handle();

        Notification::assertSentTo($admin, SubscriptionRenewalReminder::class);
    }

    public function test_does_not_send_when_subscription_not_expiring_soon(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->addDays(10), 'is_active' => true]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        (new SendRenewalReminders)->handle();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_to_inactive_tenants(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->addDays(3), 'is_active' => false]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        (new SendRenewalReminders)->handle();

        Notification::assertNothingSent();
    }

    public function test_does_not_throw_when_no_admin_found(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->addDays(3), 'is_active' => true]);

        // User with no Admin role assigned
        User::factory()->create(['tenant_id' => $tenant->id]);

        (new SendRenewalReminders)->handle();

        Notification::assertNothingSent();
    }

    public function test_records_audit_log_for_each_notified_tenant(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['subscription_ends_at' => now()->addDays(5), 'is_active' => true]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        (new SendRenewalReminders)->handle();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.renewal_reminder',
            'model_type' => Tenant::class,
            'model_id' => $tenant->id,
        ]);
    }

    public function test_sends_to_all_tenants_expiring_within_7_days(): void
    {
        // Tenant 1 — expira en 3 días
        $tenant1 = $this->setUpTenant();
        $tenant1->update(['subscription_ends_at' => now()->addDays(3), 'is_active' => true]);
        $admin1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $admin1->assignRole('Admin');

        // Tenant 2 — expira en 6 días
        $tenant2 = Tenant::factory()->create([
            'subscription_ends_at' => now()->addDays(6),
            'is_active' => true,
        ]);
        $tenant2->makeCurrent();
        app(TenantSetupService::class)->provisionTenant($tenant2);
        $admin2 = User::factory()->create(['tenant_id' => $tenant2->id]);
        $admin2->assignRole('Admin');
        Tenant::forgetCurrent();

        // Tenant 3 — expira en 10 días (fuera del rango, no debe notificarse)
        Tenant::factory()->create([
            'subscription_ends_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        (new SendRenewalReminders)->handle();

        Notification::assertSentTo($admin1, SubscriptionRenewalReminder::class);
        Notification::assertSentTo($admin2, SubscriptionRenewalReminder::class);
        $this->assertEquals(2, AuditLog::where('action', 'subscription.renewal_reminder')->count());
    }
}
