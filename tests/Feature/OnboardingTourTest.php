<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class OnboardingTourTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tenant_dashboard_shares_first_time_onboarding_state(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('onboarding.show_tour', true)
                ->where('onboarding.completed_at', null)
            );
    }

    public function test_completed_onboarding_tour_is_hidden_for_super_admins(): void
    {
        $user = User::factory()->superAdmin()->create([
            'onboarding_tour_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('onboarding.show_tour', false)
                ->where('onboarding.completed_at', $user->onboarding_tour_completed_at?->toIso8601String())
            );
    }

    public function test_authenticated_users_can_complete_their_onboarding_tour(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding-tour.complete'))
            ->assertNoContent();

        $user->refresh();

        $this->assertNotNull($user->onboarding_tour_completed_at);

        $this->actingAs($user)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('onboarding.show_tour', false)
            );
    }

    public function test_tenant_dashboard_shares_empty_completed_sections_by_default(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('onboarding.completed_sections', [])
            );
    }

    public function test_authenticated_users_can_complete_a_section_tour_without_affecting_the_global_flag(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('onboarding-tour.complete'), ['section' => 'dashboard'])
            ->assertNoContent();

        $user->refresh();

        $this->assertSame(['dashboard'], $user->onboarding_sections_completed);
        $this->assertNotNull($user->onboarding_tour_completed_at);

        $this->actingAs($user)
            ->post(route('onboarding-tour.complete'), ['section' => 'dashboard'])
            ->assertNoContent();

        $user->refresh();

        $this->assertSame(['dashboard'], $user->onboarding_sections_completed);

        $this->actingAs($user)
            ->post(route('onboarding-tour.complete'), ['section' => 'clients'])
            ->assertNoContent();

        $user->refresh();

        $this->assertSame(['dashboard', 'clients'], $user->onboarding_sections_completed);
    }

    public function test_section_tour_rejects_unknown_sections(): void
    {
        $tenant = $this->setUpTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('onboarding-tour.complete'), ['section' => 'not-a-section'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section');

        $this->assertEmpty($user->refresh()->onboarding_sections_completed ?? []);
    }

    public function test_completing_the_global_tour_keeps_its_original_timestamp(): void
    {
        $tenant = $this->setUpTenant();
        $completedAt = now()->subDays(3)->startOfSecond();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'onboarding_tour_completed_at' => $completedAt,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding-tour.complete'))
            ->assertNoContent();

        $this->assertTrue($user->refresh()->onboarding_tour_completed_at->equalTo($completedAt));
    }

    public function test_guests_cannot_complete_the_tour(): void
    {
        $this->post(route('onboarding-tour.complete'))->assertRedirect();
    }
}
