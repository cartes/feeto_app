<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\EmailTracking;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantDirectEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantDirectEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Soporte Taller Flow',
        ]);
    }

    public function test_activity_page_exposes_email_trackings_and_quick_templates(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Taller Los Andes', 'slug' => 'taller-los-andes']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'admin@losandes.cl']);

        EmailTracking::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'type' => 'admin_direct',
            'recipient_email' => $admin->email,
            'subject' => 'Bienvenido al sistema',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tenants.activity', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Tenants/Activity')
                ->has('email_trackings', 1)
                ->has('quick_templates', 4)
            );
    }

    public function test_super_admin_can_send_direct_email_to_tenant_admin_with_variable_interpolation(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['name' => 'Taller Norte', 'slug' => 'taller-norte']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Carlos Pérez',
            'email' => 'carlos@tallernorte.cl',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.send-email', $tenant), [
                'recipient_mode' => 'admin',
                'template_key' => 'onboarding',
                'subject' => 'Hola {nombre_usuario} de {nombre_taller}',
                'body' => 'Tu link público es {link_publico} y tu panel es {link_panel}.',
                'action_label' => 'Entrar a {nombre_taller}',
                'action_url' => 'https://tallerflow.cl/taller/taller-norte/dashboard',
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        Notification::assertSentTo($admin, TenantDirectEmailNotification::class, function (TenantDirectEmailNotification $notification) use ($admin): bool {
            $mail = $notification->toMail($admin);
            $html = (string) view($mail->view, $mail->viewData)->render();

            return $notification->emailSubject === 'Hola Carlos Pérez de Taller Norte'
                && str_contains($notification->emailBody, '/taller/taller-norte')
                && str_contains($html, 'Entrar a Taller Norte')
                && str_contains($html, '/mail/track/');
        });

        $tracking = EmailTracking::where('tenant_id', $tenant->id)
            ->where('type', 'admin_direct')
            ->first();

        $this->assertNotNull($tracking);
        $this->assertSame('Hola Carlos Pérez de Taller Norte', $tracking->subject);
        $this->assertSame('carlos@tallernorte.cl', $tracking->recipient_email);
        $this->assertSame('https://tallerflow.cl/taller/taller-norte/dashboard', $tracking->metadata['action_url'] ?? null);
    }

    public function test_super_admin_can_send_direct_email_to_specific_user_or_all_users(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['name' => 'Taller Sur']);
        $userA = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Ana', 'email' => 'ana@sur.cl']);
        $userB = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Pedro', 'email' => 'pedro@sur.cl']);

        // Envío a un usuario específico
        $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.send-email', $tenant), [
                'recipient_mode' => 'user',
                'user_id' => $userB->id,
                'subject' => 'Mensaje para {nombre_usuario}',
                'body' => 'Hola {nombre_usuario}',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($userB, TenantDirectEmailNotification::class);
        Notification::assertNotSentTo($userA, TenantDirectEmailNotification::class);

        // Envío a todos los usuarios del taller
        Notification::fake();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.tenants.send-email', $tenant), [
                'recipient_mode' => 'all',
                'subject' => 'Aviso general para {nombre_taller}',
                'body' => 'Estimado {nombre_usuario}, actualizamos el sistema.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($userA, TenantDirectEmailNotification::class);
        Notification::assertSentTo($userB, TenantDirectEmailNotification::class);
        $this->assertSame(3, EmailTracking::where('tenant_id', $tenant->id)->count());
    }

    public function test_tracked_click_redirects_to_custom_action_url_when_present(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'taller-centro']);
        $tracking = EmailTracking::create([
            'tenant_id' => $tenant->id,
            'type' => 'admin_direct',
            'recipient_email' => 'contacto@centro.cl',
            'token' => 'custom-click-token-12345678901234567890',
            'subject' => 'Prueba de clic',
            'metadata' => [
                'action_url' => 'https://tallerflow.cl/taller/taller-centro',
            ],
        ]);

        $this->get(route('mail.click', ['token' => $tracking->token]))
            ->assertRedirect('https://tallerflow.cl/taller/taller-centro');

        $this->assertSame(1, $tracking->fresh()->click_count);
        $this->assertNotNull($tracking->fresh()->clicked_at);
    }

    public function test_non_super_admin_cannot_send_direct_email(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $regularUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($regularUser)
            ->post(route('admin.tenants.send-email', $tenant), [
                'recipient_mode' => 'admin',
                'subject' => 'Intento no autorizado',
                'body' => 'Contenido',
            ])
            ->assertForbidden();

        Notification::assertNothingSent();
    }
}
