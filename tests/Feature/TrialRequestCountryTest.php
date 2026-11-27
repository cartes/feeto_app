<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrialRequestCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_request_stores_country_chile(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();

        $response = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Pedro Soto',
            'email' => 'pedro@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Pedro',
            'rut_taller' => '12.345.678-5',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);

        $response->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', [
            'email' => 'pedro@taller.cl',
            'country' => 'CL',
            'rut_taller' => '12345678-5',
        ]);
    }

    public function test_trial_request_stores_country_colombia(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();

        $response = $this->post(route('trial.store'), [
            'country' => 'CO',
            'name' => 'María García',
            'email' => 'maria@taller.co',
            'phone' => '+573001234567',
            'business_name' => 'Taller María',
            'rut_taller' => '900123456-8',
            'business_type' => 'Taller mecánico',
            'city' => 'Bogotá',
            'terms' => true,
        ]);

        $response->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', [
            'email' => 'maria@taller.co',
            'country' => 'CO',
            'rut_taller' => '900123456-8',
        ]);
    }

    public function test_trial_request_requires_valid_and_unique_rut_taller(): void
    {
        // 1. Requerido
        $missingResponse = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Pedro Soto',
            'email' => 'pedro-missing@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Pedro',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);
        $missingResponse->assertSessionHasErrors(['rut_taller']);

        // 2. Dígito verificador inválido
        $invalidResponse = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Pedro Soto',
            'email' => 'pedro-invalid@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Pedro',
            'rut_taller' => '12345678-0',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);
        $invalidResponse->assertSessionHasErrors(['rut_taller']);

        // 3. Único respecto a talleres (tenants) existentes
        Tenant::factory()->create([
            'rut_taller' => '12345678-5',
        ]);

        $duplicateTenantResponse = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Pedro Soto',
            'email' => 'pedro-dup-tenant@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Pedro',
            'rut_taller' => '12.345.678-5',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);
        $duplicateTenantResponse->assertSessionHasErrors(['rut_taller']);

        // 4. Único respecto a otras solicitudes de prueba (trial_requests)
        TrialRequest::create([
            'name' => 'Solicitud Previa',
            'email' => 'previa@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Previo',
            'rut_taller' => '11111111-1',
            'business_type' => 'Taller mecánico',
            'country' => 'CL',
            'status' => 'pending',
        ]);

        $duplicateTrialResponse = $this->post(route('trial.store'), [
            'country' => 'CL',
            'name' => 'Pedro Soto',
            'email' => 'pedro-dup-trial@taller.cl',
            'phone' => '+56911111111',
            'business_name' => 'Taller Pedro',
            'rut_taller' => '11.111.111-1',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);
        $duplicateTrialResponse->assertSessionHasErrors(['rut_taller']);
    }

    public function test_trial_request_rejects_invalid_country(): void
    {
        $response = $this->post(route('trial.store'), [
            'country' => 'BR',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+5511111111',
            'business_name' => 'Test Taller',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['country']);

        $this->assertDatabaseMissing('trial_requests', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_trial_request_requires_country(): void
    {
        $response = $this->post(route('trial.store'), [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'phone' => '+56911111111',
            'business_name' => 'Test Taller',
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['country']);
    }

    public function test_approved_trial_propagates_country_to_tenant(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->superAdmin()->create();

        $trialRequest = TrialRequest::create([
            'name' => 'Carlos Martínez',
            'email' => 'carlos@taller.co',
            'phone' => '+573009876543',
            'business_name' => 'Taller CarlosCO',
            'rut_taller' => '900123456-8',
            'business_type' => 'Taller mecánico',
            'city' => 'Medellín',
            'country' => 'CO',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->post(
            route('admin.trial-requests.approve', $trialRequest),
            ['admin_password' => 'PasswordSeguro123'],
        );

        $response->assertRedirect();

        $trialRequest->refresh();
        $this->assertSame('approved', $trialRequest->status);
        $this->assertNotNull($trialRequest->tenant_id);
        $this->assertTrue(Tenant::query()->findOrFail($trialRequest->tenant_id)->setup_checklist['welcome']['required']);

        $this->assertDatabaseHas('tenants', [
            'id' => $trialRequest->tenant_id,
            'country' => 'CO',
            'rut_taller' => '900123456-8',
        ]);
    }

    public function test_create_page_passes_countries_to_frontend(): void
    {
        $response = $this->get(route('trial.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trial/Create')
            ->has('countries', 2)
            ->where('countries.0.value', 'CL')
            ->where('countries.0.flag', '🇨🇱')
            ->where('countries.1.value', 'CO')
            ->where('countries.1.flag', '🇨🇴')
        );
    }
}
