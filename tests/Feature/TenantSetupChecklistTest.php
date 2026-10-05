<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupChecklistService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantSetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        app(TenantSetupService::class)->provisionTenant($this->tenant, $this->admin);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    public function test_existing_tenant_starts_with_five_pending_steps_and_direct_links(): void
    {
        $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('setupChecklist.steps', 5)
            ->where('setupChecklist.completed_count', 0)
            ->where('setupChecklist.is_complete', false)
            ->where('setupChecklist.steps.0.url', route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'branches']))
            ->where('setupChecklist.steps.2.url', route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'scheduling']))
            ->where('setupChecklist.steps.3.url', route('taller.landing', ['tenantBySlug' => $this->tenant->slug]))
            ->where('setupChecklist.steps.3.external', true)
            ->where('setupChecklist.steps.4.url', route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'website']))
            );
    }

    public function test_progress_is_persisted_and_shared_with_another_admin(): void
    {
        $this->updateStep('business_details')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertArrayHasKey('business_details', $this->tenant->fresh()->setup_checklist);

        $secondAdmin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $secondAdmin->assignRole('Admin');
        $this->actingAs($secondAdmin)->get(route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'website']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('setupChecklist.completed_count', 1)
            ->where('setupChecklist.steps.0.completed', true)
            ->where('setupChecklist.steps.4.completed', false)
            );
    }

    public function test_repeated_completion_keeps_the_original_timestamp(): void
    {
        $this->updateStep('public_page')->assertSessionHasNoErrors();
        $timestamp = $this->tenant->fresh()->setup_checklist['public_page'];
        $this->travel(1)->hours();
        $this->updateStep('public_page')->assertSessionHasNoErrors();
        $this->assertSame($timestamp, $this->tenant->fresh()->setup_checklist['public_page']);
    }

    public function test_completed_step_can_be_reopened_without_losing_other_steps(): void
    {
        $this->updateStep('public_page');
        $this->updateStep('scheduling');
        $this->updateStep('public_page', false)->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('public_page', $this->tenant->fresh()->setup_checklist);
        $this->assertArrayHasKey('scheduling', $this->tenant->fresh()->setup_checklist);
    }

    public function test_all_steps_are_required_before_the_checklist_is_complete(): void
    {
        foreach (TenantSetupChecklistService::STEPS as $step) {
            $this->updateStep($step)->assertSessionHasNoErrors();
        }

        $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('setupChecklist.completed_count', 5)
                ->where('setupChecklist.is_complete', true)
            );

        $this->updateStep('share_link', false);
        $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('setupChecklist.completed_count', 4)
                ->where('setupChecklist.is_complete', false)
            );
    }

    public function test_invalid_steps_and_invalid_completion_state_are_rejected(): void
    {
        $this->patch($this->updateUrl(), ['step' => 'billing', 'completed' => true])->assertSessionHasErrors('step');
        $this->patch($this->updateUrl(), ['step' => 'scheduling', 'completed' => 'yes'])->assertSessionHasErrors('completed');
        $this->patch($this->updateUrl(), [])->assertSessionHasErrors(['step', 'completed']);
        $this->assertNull($this->tenant->fresh()->setup_checklist);
    }

    public function test_staff_cannot_update_or_see_the_checklist(): void
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $staff->assignRole('Mecanico');
        $this->actingAs($staff)->patch($this->updateUrl(), ['step' => 'scheduling', 'completed' => true])->assertForbidden();
        $this->get(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('setupChecklist', null));
        $this->assertNull($this->tenant->fresh()->setup_checklist);
    }

    public function test_admin_cannot_update_another_tenant_and_payload_cannot_change_the_target(): void
    {
        $otherTenant = Tenant::factory()->create();
        $this->patch(route('taller.settings.setup-checklist.update', ['tenantBySlug' => $otherTenant->slug]), ['step' => 'scheduling', 'completed' => true])->assertForbidden();
        Tenant::forgetCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->admin->unsetRelation('roles')->unsetRelation('permissions');
        $this->patch($this->updateUrl(), ['step' => 'scheduling', 'completed' => true, 'tenant_id' => $otherTenant->id])->assertSessionHasNoErrors();
        $this->assertNull($otherTenant->fresh()->setup_checklist);
        $this->assertArrayHasKey('scheduling', $this->tenant->fresh()->setup_checklist);
    }

    public function test_visiting_settings_and_public_page_does_not_complete_a_step(): void
    {
        $this->get(route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'website']))->assertOk();
        $this->get(route('taller.landing', ['tenantBySlug' => $this->tenant->slug]))->assertOk();
        $this->assertNull($this->tenant->fresh()->setup_checklist);
    }

    public function test_stale_tenant_instances_preserve_changes_from_other_admins(): void
    {
        $firstTenant = $this->tenant->fresh();
        $secondTenant = $this->tenant->fresh();
        $service = app(TenantSetupChecklistService::class);
        $service->updateStep($firstTenant, 'public_page', true);
        $service->updateStep($secondTenant, 'share_link', true);
        $this->assertCount(2, $this->tenant->fresh()->setup_checklist);
    }

    public function test_basic_plan_keeps_all_steps_accessible_without_seo_access(): void
    {
        $this->tenant->update(['plan' => 'gratuito', 'plan_type' => 'gratuito']);
        $this->get(route('taller.settings', ['tenantBySlug' => $this->tenant->slug, 'tab' => 'website']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canAccessSeo', false)
                ->has('setupChecklist.steps', 5)
                ->where('setupChecklist.steps.1.description', 'Revisa tu logo. Los datos de contacto se administran en Sucursales.')
            );
    }

    public function test_guest_cannot_update_progress(): void
    {
        auth()->logout();
        $this->patch($this->updateUrl(), ['step' => 'scheduling', 'completed' => true])->assertRedirect(route('login'));
        $this->assertNull($this->tenant->fresh()->setup_checklist);
    }

    private function updateUrl(): string
    {
        return route('taller.settings.setup-checklist.update', ['tenantBySlug' => $this->tenant->slug]);
    }

    private function updateStep(string $step, bool $completed = true): TestResponse
    {
        return $this->from(route('taller.dashboard', ['tenantBySlug' => $this->tenant->slug]))
            ->patch($this->updateUrl(), ['step' => $step, 'completed' => $completed]);
    }
}
