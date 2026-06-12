<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantSchedulingControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_update_scheduling_config(): void
    {
        $tenant = $this->setUpTenant();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('Admin');

        $payload = [
            'slot_duration' => 60,
            'days' => [
                'monday' => ['enabled' => true, 'open' => '09:00:00', 'close' => '18:00:00'],
                'tuesday' => ['enabled' => true, 'open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['enabled' => true, 'open' => '09:00', 'close' => '18:00'],
                'thursday' => ['enabled' => true, 'open' => '09:00', 'close' => '18:00'],
                'friday' => ['enabled' => true, 'open' => '09:00', 'close' => '18:00'],
                'saturday' => ['enabled' => false, 'open' => '09:00', 'close' => '14:00'],
                'sunday' => ['enabled' => false, 'open' => '09:00', 'close' => '14:00'],
            ],
            'blocked_slots' => [
                [
                    'id' => 'slot_1',
                    'date' => '2026-06-20',
                    'start' => '10:00:00',
                    'end' => '11:00:00',
                    'reason' => 'Meeting',
                ],
            ],
            'blocked_dates' => [],
        ];

        $response = $this->actingAs($admin)
            ->patch(route('taller.settings.scheduling.update', ['tenantBySlug' => $tenant->slug]), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $tenant->refresh();
        $config = $tenant->schedulingConfig();
        $this->assertEquals(60, $config['slot_duration']);
        $this->assertEquals('09:00', $config['days']['monday']['open']);
        $this->assertEquals('18:00', $config['days']['monday']['close']);
        $this->assertEquals('10:00', $config['blocked_slots'][0]['start']);
        $this->assertEquals('11:00', $config['blocked_slots'][0]['end']);
    }
}
