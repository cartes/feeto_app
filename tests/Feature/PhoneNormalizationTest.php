<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Country;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PhoneNormalizationTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_country_enum_normalizes_local_and_international_phone_numbers(): void
    {
        // Chile (+56)
        $this->assertSame('+56989020072', Country::Chile->normalizePhoneNumber('989020072'));
        $this->assertSame('+56989020072', Country::Chile->normalizePhoneNumber('9 8902 0072'));
        $this->assertSame('+56989020072', Country::Chile->normalizePhoneNumber('+56 9 8902 0072'));
        $this->assertSame('+56989020072', Country::Chile->normalizePhoneNumber('+56989020072'));
        $this->assertSame('+56989020072', Country::Chile->normalizePhoneNumber('56989020072'));

        // Colombia (+57)
        $this->assertSame('+573001234567', Country::Colombia->normalizePhoneNumber('3001234567'));
        $this->assertSame('+573001234567', Country::Colombia->normalizePhoneNumber('+57 300 123 4567'));

        // Argentina (+54)
        $this->assertSame('+5491112345678', Country::Argentina->normalizePhoneNumber('91112345678'));
        $this->assertSame('+5491112345678', Country::Argentina->normalizePhoneNumber('+54 9 11 1234 5678'));

        // Cliente extranjero en taller chileno
        $this->assertSame('+5491112345678', Country::Chile->normalizePhoneNumber('+54 9 11 1234 5678'));

        // Vacíos o nulos
        $this->assertNull(Country::Chile->normalizePhoneNumber(null));
        $this->assertNull(Country::Chile->normalizePhoneNumber('   '));
    }

    public function test_store_client_normalizes_phone_number_with_tenant_country(): void
    {
        $tenant = $this->setUpTenant();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);

        $response = $this->actingAs($admin)->post(route('clients.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'Carlos Conductor',
            'rut' => '11111111-1',
            'phone' => '9 8902 0072', // Sin prefijo explícito
            'email' => 'carlos@example.com',
        ]);

        $response->assertSessionHasNoErrors();

        $client = Client::where('rut', '11111111-1')->first();
        $this->assertNotNull($client);
        $this->assertSame('+56989020072', $client->phone);
    }

    public function test_store_client_preserves_explicit_international_prefix(): void
    {
        $tenant = $this->setUpTenant();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);

        $response = $this->actingAs($admin)->post(route('clients.store', ['tenantBySlug' => $tenant->slug]), [
            'name' => 'Martín Turista',
            'rut' => '22222222-2',
            'phone' => '+54 9 11 1234 5678', // Cliente argentino
            'email' => 'martin@example.com',
        ]);

        $response->assertSessionHasNoErrors();

        $client = Client::where('rut', '22222222-2')->first();
        $this->assertNotNull($client);
        $this->assertSame('+5491112345678', $client->phone);
    }
}
