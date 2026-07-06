<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendInactivityReminders;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInactivityReminder;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SendInactivityRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    private function createProvisionedTenant(array $attributes = []): Tenant
    {
        $tenant = Tenant::factory()->create($attributes);
        app(TenantSetupService::class)->provisionTenant($tenant);

        return $tenant;
    }

    /** @test */
    public function test_tenant_inactive_for_more_than_10_days_receives_reminder(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant([
            'name' => 'Auto Okey',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        // Login log de hace 11 días
        LoginLog::forceCreate([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(11),
        ]);

        (new SendInactivityReminders)->handle();

        Notification::assertSentTo($admin, TenantInactivityReminder::class, function ($notification) use ($tenant) {
            return $notification->tenant->id === $tenant->id;
        });

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.inactivity_reminder_sent',
            'model_id' => $tenant->id,
        ]);
    }

    /** @test */
    public function test_tenant_active_recently_does_not_receive_reminder(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant(['is_active' => true]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        // Login log de hace 2 días
        LoginLog::forceCreate([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(2),
        ]);

        (new SendInactivityReminders)->handle();

        Notification::assertNotSentTo($admin, TenantInactivityReminder::class);
    }

    /** @test */
    public function test_tenant_never_logged_in_but_created_long_ago_receives_reminder(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant([
            'is_active' => true,
        ]);
        $tenant->created_at = now()->subDays(11);
        $tenant->save();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        (new SendInactivityReminders)->handle();

        Notification::assertSentTo($admin, TenantInactivityReminder::class);
    }

    /** @test */
    public function test_tenant_never_logged_in_but_recently_created_does_not_receive_reminder(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant([
            'is_active' => true,
        ]);
        $tenant->created_at = now()->subDays(2);
        $tenant->save();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        (new SendInactivityReminders)->handle();

        Notification::assertNotSentTo($admin, TenantInactivityReminder::class);
    }

    /** @test */
    public function test_inactive_tenant_does_not_receive_reminder(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant([
            'is_active' => false,
        ]);
        $tenant->created_at = now()->subDays(11);
        $tenant->save();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        (new SendInactivityReminders)->handle();

        Notification::assertNotSentTo($admin, TenantInactivityReminder::class);
    }

    /** @test */
    public function test_tenant_already_sent_reminder_recently_does_not_receive_it_again(): void
    {
        Notification::fake();

        $tenant = $this->createProvisionedTenant([
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();
        $admin->assignRole('Admin');
        Tenant::forgetCurrent();

        // Login log de hace 11 días
        LoginLog::forceCreate([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(11),
        ]);

        // Registrar envío de recordatorio hace 2 días en bitácora
        AuditLog::forceCreate([
            'action' => 'tenant.inactivity_reminder_sent',
            'model_type' => Tenant::class,
            'model_id' => $tenant->id,
            'created_at' => now()->subDays(2),
            'description' => 'fake reminder sent',
        ]);

        (new SendInactivityReminders)->handle();

        Notification::assertNotSentTo($admin, TenantInactivityReminder::class);
    }
}
