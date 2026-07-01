<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\FastPlateRecognitionAgent;
use App\Ai\Agents\PatentRecognitionAgent;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Providers\GeminiProvider;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SmartReceptionControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_scan_plate_uses_the_fast_agent_and_returns_todays_appointment_context(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();

        $admin = $this->createAdmin($tenant);

        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pedro Cliente',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'plate' => 'GKSB78',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'color' => 'Blanco',
        ]);

        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'plate' => 'GKSB78',
            'customer_name' => $client->name,
            'phone' => $client->phone,
            'appointment_date' => now()->setTime(10, 30),
            'status' => 'pending',
            'notes' => 'Cambio de aceite',
        ]);

        FastPlateRecognitionAgent::fake([
            [
                'plate' => 'GKSB78',
            ],
        ])->preventStrayPrompts();

        $response = $this->actingAs($admin)->postJson(route('api.appointments.scan-plate', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('plate.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'plate' => 'GKSB78',
                'confidence' => null,
                'vehicle' => [
                    'brand' => 'Toyota',
                    'model' => 'Hilux',
                    'color' => 'Blanco',
                ],
                'appointment' => [
                    'id' => $appointment->id,
                    'time' => '10:30',
                    'status' => 'pending',
                    'notes' => 'Cambio de aceite',
                    'client' => [
                        'name' => 'Pedro Cliente',
                        'rut' => '11111111-1',
                        'phone' => '+56911111111',
                    ],
                    'vehicle' => [
                        'brand' => 'Toyota',
                        'model' => 'Hilux',
                        'color' => 'Blanco',
                    ],
                ],
            ]);

        FastPlateRecognitionAgent::assertPrompted(function ($prompt): bool {
            return $prompt->prompt === 'Extrae unicamente la patente chilena visible en esta imagen.'
                && $prompt->provider() instanceof GeminiProvider
                && $prompt->model === 'gemini-3.1-flash-lite-preview';
        });

        PatentRecognitionAgent::assertNotPrompted(
            'Lee la patente de este vehiculo chileno e identifica marca, modelo y color. Devuelve la respuesta estructurada.'
        );
    }

    public function test_scan_plate_does_not_fallback_to_detailed_recognition_when_the_appointment_is_found(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();

        $admin = $this->createAdmin($tenant);

        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pedro Cliente',
            'rut' => '11111111-1',
            'phone' => '+56911111111',
        ]);

        Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'vehicle_id' => null,
            'plate' => 'GKSB78',
            'customer_name' => $client->name,
            'phone' => $client->phone,
            'appointment_date' => now()->setTime(10, 30),
            'status' => 'pending',
            'notes' => 'Cambio de aceite',
        ]);

        FastPlateRecognitionAgent::fake([
            [
                'plate' => 'GKSB78',
            ],
        ])->preventStrayPrompts();

        $response = $this->actingAs($admin)->postJson(route('api.appointments.scan-plate', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('plate.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'plate' => 'GKSB78',
                'confidence' => null,
                'vehicle' => [
                    'brand' => null,
                    'model' => null,
                    'color' => null,
                ],
                'appointment' => [
                    'status' => 'pending',
                    'notes' => 'Cambio de aceite',
                    'client' => [
                        'name' => 'Pedro Cliente',
                        'rut' => '11111111-1',
                        'phone' => '+56911111111',
                    ],
                    'vehicle' => null,
                ],
            ]);

        PatentRecognitionAgent::assertNotPrompted(
            'Lee la patente de este vehiculo chileno e identifica marca, modelo y color. Devuelve la respuesta estructurada.'
        );
    }

    public function test_scan_plate_falls_back_to_detailed_recognition_when_fast_recognition_cannot_extract_a_plate(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->forceFill([
            'plan' => 'profesional',
            'plan_type' => 'profesional',
        ])->save();

        $admin = $this->createAdmin($tenant);

        FastPlateRecognitionAgent::fake([
            [
                'plate' => '',
            ],
        ])->preventStrayPrompts();

        PatentRecognitionAgent::fake([
            [
                'plate' => 'ZZ9911',
                'brand' => 'Mazda',
                'model' => 'CX5',
                'color' => 'Rojo',
                'confidence' => 0.91,
            ],
        ])->preventStrayPrompts();

        $response = $this->actingAs($admin)->postJson(route('api.appointments.scan-plate', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'image' => UploadedFile::fake()->image('plate.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'plate' => 'ZZ9911',
                'confidence' => 0.91,
                'vehicle' => [
                    'brand' => 'Mazda',
                    'model' => 'CX5',
                    'color' => 'Rojo',
                ],
                'appointment' => null,
            ]);

        PatentRecognitionAgent::assertPrompted(function ($prompt): bool {
            return $prompt->prompt === 'Lee la patente de este vehiculo chileno e identifica marca, modelo y color. Devuelve la respuesta estructurada.'
                && $prompt->provider() instanceof GeminiProvider
                && $prompt->model === 'gemini-3-flash-preview';
        });
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->assignRole('Admin');

        return $admin;
    }
}
