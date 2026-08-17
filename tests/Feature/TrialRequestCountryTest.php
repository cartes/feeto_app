<?php

declare(strict_types=1);

namespace Tests\Feature;

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
            'business_type' => 'Taller mecánico',
            'terms' => true,
        ]);

        $response->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', [
            'email' => 'pedro@taller.cl',
            'country' => 'CL',
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
            'business_type' => 'Taller mecánico',
            'city' => 'Bogotá',
            'terms' => true,
        ]);

        $response->assertRedirect(route('trial.success'));

        $this->assertDatabaseHas('trial_requests', [
            'email' => 'maria@taller.co',
            'country' => 'CO',
        ]);
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

        $this->assertDatabaseHas('tenants', [
            'id' => $trialRequest->tenant_id,
            'country' => 'CO',
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
