<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupChecklistService;
use App\Services\TenantSetupService;
use App\Services\TenantWelcomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantWelcomeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->tenant = Tenant::factory()->create(['name' => 'Taller Cartes', 'comuna' => 'Concepción']);
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        app(TenantSetupService::class)->provisionTenant($this->tenant, $this->admin);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    public function test_new_workshop_opens_welcome_and_existing_workshop_gets_an_invitation(): void
    {
        $this->get($this->url('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('welcome.completed', false)->where('welcome.started', false));
        app(TenantWelcomeService::class)->start($this->tenant);
        Tenant::forgetCurrent();
        $this->get($this->url('dashboard'))->assertRedirect($this->url('welcome.show'));
        $this->get($this->url('welcome.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('TenantWelcome')->where('welcome.next_step', 1)
            ->where('welcome.profile.name', 'Taller Cartes')
            ->where('welcome.suggested_description', 'Taller Cartes es un taller automotriz en Concepción. Consulta nuestros datos de contacto y solicita una hora desde nuestra página en TallerFlow.'));
    }

    public function test_existing_profile_and_public_business_contact_are_prefilled_without_account_email(): void
    {
        $this->tenant->update(['seo_description' => 'Nuestra descripción propia.', 'seo_address' => 'Calle 123']);
        $this->tenant->mainBranch()->update(['phone' => '+56912345678', 'email' => 'taller@example.com']);
        $this->get($this->url('welcome.show'))->assertInertia(fn (Assert $page) => $page
            ->where('welcome.profile.description', 'Nuestra descripción propia.')
            ->where('welcome.profile.address', 'Calle 123')
            ->where('welcome.profile.phone', '+56912345678')
            ->where('welcome.profile.email', 'taller@example.com'));
        $this->get(route('taller.landing', ['tenantBySlug' => $this->tenant->slug]))
            ->assertInertia(fn (Assert $page) => $page->where('tenant.contact_email', null));
    }

    public function test_draft_survives_deferral_and_is_shared_without_publishing_changes(): void
    {
        app(TenantWelcomeService::class)->start($this->tenant);
        $originalName = $this->tenant->name;
        $this->saveBusiness()->assertSessionHasNoErrors();
        $this->update(['action' => 'defer'])->assertRedirect($this->url('dashboard'));
        $this->assertSame($originalName, $this->tenant->fresh()->name);
        $this->get($this->url('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('welcome.started', true));
        $anotherAdmin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $anotherAdmin->assignRole('Admin');
        $this->actingAs($anotherAdmin)->get($this->url('welcome.show'))->assertInertia(fn (Assert $page) => $page
            ->where('welcome.next_step', 2)->where('welcome.profile.name', 'Taller Renovado'));
    }

    public function test_finishing_publishes_profile_contact_and_seo_without_changing_slug_or_other_branches(): void
    {
        $slug = $this->tenant->slug;
        $branch = $this->tenant->mainBranch();
        $anotherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id, 'is_main' => false, 'phone' => '11223344']);
        app(TenantSetupChecklistService::class)->updateStep($this->tenant, 'scheduling', true);
        $this->completeSteps();
        $this->update(['action' => 'finish', 'name' => 'Injected name'])->assertRedirect($this->url('dashboard'))->assertSessionHasNoErrors();
        $tenant = $this->tenant->fresh();
        $this->assertSame($slug, $tenant->slug);
        $this->assertSame('Taller Renovado', $tenant->name);
        $this->assertSame('Presentación real del taller.', $tenant->seo_description);
        $this->assertSame('+56912345678', $branch->fresh()->phone);
        $this->assertSame('taller@example.com', $branch->fresh()->email);
        $this->assertSame('11223344', $anotherBranch->fresh()->phone);
        $this->assertArrayHasKey('business_details', $tenant->setup_checklist);
        $this->assertArrayHasKey('scheduling', $tenant->setup_checklist);
        $this->assertArrayNotHasKey('public_page', $tenant->setup_checklist);
        $this->assertArrayNotHasKey('draft', $tenant->setup_checklist['welcome']);
        $this->get($this->url('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('welcome.completed', true));
        $this->get($this->url('welcome.show'))->assertRedirect($this->url('dashboard'));
        $this->get(route('taller.landing', ['tenantBySlug' => $slug]))->assertOk()
            ->assertSee('name="description" content="Presentación real del taller."', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('tenant.name', 'Taller Renovado')
                ->where('tenant.contact_email', 'taller@example.com')
                ->where('seo.description', 'Presentación real del taller.')
                ->where('seo.schema.0.address.addressLocality', 'Concepción')
                ->where('seo.schema.0.telephone', '+56912345678'));
    }

    public function test_finish_is_idempotent_and_a_completed_profile_cannot_be_overwritten_by_stale_steps(): void
    {
        $this->completeSteps();
        $this->update(['action' => 'finish'])->assertSessionHasNoErrors();
        $completedAt = $this->tenant->fresh()->setup_checklist['welcome']['completed_at'];
        $this->travel(1)->hours();
        $this->update(['action' => 'finish'])->assertSessionHasNoErrors();
        $this->assertSame($completedAt, $this->tenant->fresh()->setup_checklist['welcome']['completed_at']);
        $this->saveBusiness()->assertSessionHasErrors('step');
    }

    public function test_validation_prevents_skipping_steps_and_invalid_public_data(): void
    {
        $this->update(['action' => 'finish'])->assertSessionHasErrors('step');
        $this->update(['action' => 'save', 'step' => 3, 'description' => 'Intento de saltar pasos.'])->assertSessionHasErrors('step');
        $this->update(['action' => 'save', 'step' => 1, 'name' => '', 'comuna' => ''])->assertSessionHasErrors(['name', 'comuna']);
        $this->saveBusiness();
        $this->update(['action' => 'save', 'step' => 2, 'phone' => 'invalid', 'email' => 'bad-email', 'whatsapp_number' => 'bad'])->assertSessionHasErrors(['phone', 'email', 'whatsapp_number']);
        $this->update(['action' => 'save', 'step' => 2, 'phone' => '-------'])->assertSessionHasErrors('phone');
        $this->update(['action' => 'save', 'step' => 8])->assertSessionHasErrors('step');
        $this->update(['action' => 'unknown'])->assertSessionHasErrors('action');
        $this->update(['action' => 'save', 'step' => 2, 'phone' => '+56912345678'])->assertSessionHasNoErrors();
        $this->update(['action' => 'save', 'step' => 3, 'description' => str_repeat('a', 501)])->assertSessionHasErrors('description');
    }

    public function test_step_updates_ignore_unvalidated_fields_and_keep_other_checklist_progress(): void
    {
        $this->saveBusiness()->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('slug', $this->tenant->fresh()->setup_checklist['welcome']['draft']);
        app(TenantSetupChecklistService::class)->updateStep($this->tenant, 'scheduling', true);
        $this->assertSame(2, $this->tenant->fresh()->setup_checklist['welcome']['next_step']);
    }

    public function test_optional_address_and_contact_fields_can_be_empty_and_locality_remains_in_public_schema(): void
    {
        $this->update(['action' => 'save', 'step' => 1, 'name' => 'Taller Cartes', 'comuna' => 'Concepción', 'address' => ''])->assertSessionHasNoErrors();
        $this->update(['action' => 'save', 'step' => 2, 'phone' => '+56912345678', 'email' => '', 'whatsapp_number' => ''])->assertSessionHasNoErrors();
        $this->update(['action' => 'save', 'step' => 3, 'description' => 'Taller en Concepción.'])->assertSessionHasNoErrors();
        $this->update(['action' => 'finish'])->assertSessionHasNoErrors();
        $this->get(route('taller.landing', ['tenantBySlug' => $this->tenant->slug]))->assertInertia(fn (Assert $page) => $page
            ->where('tenant.contact_email', null)
            ->where('seo.schema.0.address.addressLocality', 'Concepción')
            ->missing('seo.schema.0.address.streetAddress'));
    }

    public function test_staff_never_gets_redirected_and_cannot_view_or_change_welcome(): void
    {
        app(TenantWelcomeService::class)->start($this->tenant);
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($staff)->get($this->url('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('welcome', null));
        $this->get($this->url('welcome.show'))->assertForbidden();
        $this->update(['action' => 'defer'])->assertForbidden();
    }

    public function test_other_tenant_and_guests_cannot_access_welcome(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherAdmin = User::factory()->create(['tenant_id' => $otherTenant->id]);
        app(TenantSetupService::class)->provisionTenant($otherTenant, $otherAdmin);
        $this->actingAs($otherAdmin)->get($this->url('welcome.show'))->assertForbidden();
        $this->update(['action' => 'defer'])->assertForbidden();
        auth()->logout();
        $this->get($this->url('welcome.show'))->assertRedirect(route('login'));
    }

    private function completeSteps(): void
    {
        $this->saveBusiness()->assertSessionHasNoErrors();
        $this->update(['action' => 'save', 'step' => 2, 'phone' => '+56912345678', 'email' => 'taller@example.com', 'whatsapp_number' => '+56987654321'])->assertSessionHasNoErrors();
        $this->update(['action' => 'save', 'step' => 3, 'description' => 'Presentación real del taller.'])->assertSessionHasNoErrors();
    }

    private function saveBusiness(): TestResponse
    {
        return $this->update(['action' => 'save', 'step' => 1, 'name' => 'Taller Renovado', 'comuna' => 'Concepción', 'address' => 'Calle Principal 123', 'slug' => 'injected']);
    }

    /** @param array<string, mixed> $data */
    private function update(array $data): TestResponse
    {
        return $this->from($this->url('welcome.show'))->patch($this->url('welcome.update'), $data);
    }

    private function url(string $name): string
    {
        return route('taller.'.$name, ['tenantBySlug' => $this->tenant->slug]);
    }
}
