<?php

namespace Tests\Feature\Admin;

use App\Enums\MarketingEmailTemplate;
use App\Models\EmailTracking;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantMarketingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TenantMarketingEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_super_admin_can_send_template_to_selected_tenants(): void
    {
        Notification::fake();

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [$tenantA->id, $tenantB->id],
            'template' => MarketingEmailTemplate::CustomTool->value,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        Notification::assertSentTo($adminA, TenantMarketingNotification::class);
        Notification::assertSentTo($adminB, TenantMarketingNotification::class);
        $this->assertSame(2, EmailTracking::where('type', 'marketing_custom_tool')->count());
    }

    public function test_tenants_without_admin_are_skipped_and_reported(): void
    {
        Notification::fake();

        $withAdmin = Tenant::factory()->create();
        $withoutAdmin = Tenant::factory()->create(['name' => 'Taller Sin Admin']);
        User::factory()->create(['tenant_id' => $withAdmin->id]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [$withAdmin->id, $withoutAdmin->id],
            'template' => 'custom_tool',
        ]);

        $response->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Taller Sin Admin'));
        Notification::assertCount(1);
    }

    public function test_returns_error_when_no_tenant_has_an_admin(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();

        $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [$tenant->id],
            'template' => 'custom_tool',
        ])->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    public function test_validates_selection_and_template(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [],
            'template' => 'inexistente',
        ])->assertSessionHasErrors(['tenant_ids', 'template']);

        $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [999999],
            'template' => 'custom_tool',
        ])->assertSessionHasErrors('tenant_ids.0');
    }

    public function test_non_super_admin_cannot_send(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [$tenant->id],
            'template' => 'custom_tool',
        ])->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_email_renders_with_tenant_data(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Taller Prueba']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Juan']);

        $mail = (new TenantMarketingNotification($tenant, MarketingEmailTemplate::CustomTool))->toMail($admin);
        $html = (string) view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Taller Prueba', $html);
        $this->assertStringContainsString('Juan', $html);
        $this->assertStringContainsString('herramienta', mb_strtolower($mail->subject));
    }

    public function test_index_exposes_marketing_templates(): void
    {
        $this->actingAs($this->superAdmin)->get(route('admin.tenants.index'))
            ->assertInertia(fn ($page) => $page->has('marketingTemplates', 1));
    }

    public function test_opted_out_tenants_are_not_emailed(): void
    {
        Notification::fake();

        $optedOut = Tenant::factory()->create(['name' => 'Taller Baja']);
        $active = Tenant::factory()->create();
        $optedOut->forceFill(['marketing_opt_out_at' => now()])->save();
        User::factory()->create(['tenant_id' => $optedOut->id]);
        User::factory()->create(['tenant_id' => $active->id]);

        $this->actingAs($this->superAdmin)->post(route('admin.tenants.send-marketing-email'), [
            'tenant_ids' => [$optedOut->id, $active->id],
            'template' => 'custom_tool',
        ])->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Taller Baja'));

        Notification::assertCount(1);
    }

    public function test_signed_unsubscribe_link_marks_tenant_as_opted_out(): void
    {
        $tenant = Tenant::factory()->create();

        $this->get(URL::signedRoute('marketing.unsubscribe', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('no te enviaremos más novedades');

        $this->assertNotNull($tenant->fresh()->marketing_opt_out_at);
    }

    public function test_unsigned_unsubscribe_link_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();

        $this->get(route('marketing.unsubscribe', ['tenant' => $tenant->id]))->assertForbidden();

        $this->assertNull($tenant->fresh()->marketing_opt_out_at);
    }

    public function test_email_contains_signed_unsubscribe_link(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        $mail = (new TenantMarketingNotification($tenant, MarketingEmailTemplate::CustomTool))->toMail($admin);
        $html = (string) view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('/marketing/baja/'.$tenant->id.'?signature=', $html);
    }
}
