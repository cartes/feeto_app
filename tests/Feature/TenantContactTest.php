<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\TenantContactMail;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantContactTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->tenant = Tenant::factory()->create(['slug' => 'test-taller']);
    }

    public function test_contact_submission_sends_email_general(): void
    {
        Mail::fake();

        // Create main branch for the tenant
        Branch::forceCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sucursal Principal',
            'email' => 'sucursal-principal@tallerflow.cl',
            'is_main' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('taller.contact.store', $this->tenant->slug), [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+56912345678',
            'type' => 'general',
            'message' => 'Tengo una consulta general sobre sus servicios.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('contact_success', true);

        Mail::assertSent(TenantContactMail::class, function (TenantContactMail $mail): bool {
            return $mail->hasTo('sucursal-principal@tallerflow.cl') &&
                $mail->data['name'] === 'Juan Pérez' &&
                $mail->data['type'] === 'general';
        });
    }

    public function test_contact_submission_sends_email_quote_with_vehicle_fields(): void
    {
        Mail::fake();

        // Create main branch for the tenant
        Branch::forceCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sucursal Principal',
            'email' => 'sucursal-principal@tallerflow.cl',
            'is_main' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('taller.contact.store', $this->tenant->slug), [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+56912345678',
            'type' => 'quote',
            'message' => 'Necesito cotizar un cambio de pastillas de freno.',
            'plate' => 'AB1234',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'year' => 2018,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('contact_success', true);

        Mail::assertSent(TenantContactMail::class, function (TenantContactMail $mail): bool {
            return $mail->hasTo('sucursal-principal@tallerflow.cl') &&
                $mail->data['name'] === 'Juan Pérez' &&
                $mail->data['type'] === 'quote' &&
                $mail->data['plate'] === 'AB1234' &&
                $mail->data['brand'] === 'Toyota' &&
                $mail->data['model'] === 'Yaris' &&
                $mail->data['year'] === 2018;
        });
    }

    public function test_contact_validation_rules(): void
    {
        // Email missing/invalid, name missing, message missing, type missing
        $response = $this->post(route('taller.contact.store', $this->tenant->slug), []);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'type', 'message']);

        // When type is quote, vehicle details are required (except year)
        $response = $this->post(route('taller.contact.store', $this->tenant->slug), [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+56912345678',
            'type' => 'quote',
            'message' => 'Necesito cotizar.',
        ]);

        $response->assertSessionHasErrors(['plate', 'brand', 'model']);
    }

    public function test_contact_recipient_fallback_to_admin(): void
    {
        Mail::fake();

        // Create an admin user for the tenant, and no branches
        $admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin-taller@tallerflow.cl',
        ]);

        $response = $this->post(route('taller.contact.store', $this->tenant->slug), [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+56912345678',
            'type' => 'general',
            'message' => 'Consulta.',
        ]);

        $response->assertRedirect();

        Mail::assertSent(TenantContactMail::class, function (TenantContactMail $mail): bool {
            return $mail->hasTo('admin-taller@tallerflow.cl');
        });
    }

    public function test_contact_recipient_fallback_to_config(): void
    {
        Mail::fake();

        // No branch and no user, fallback to config
        $response = $this->post(route('taller.contact.store', $this->tenant->slug), [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+56912345678',
            'type' => 'general',
            'message' => 'Consulta.',
        ]);

        $response->assertRedirect();

        Mail::assertSent(TenantContactMail::class, function (TenantContactMail $mail): bool {
            return $mail->hasTo(config('mail.from.address'));
        });
    }
}
