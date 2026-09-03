<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\SupportAssistantAgent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SupportAssistantControllerTest extends TestCase
{
    use CreatesTenant;
    use RefreshDatabase;

    public function test_faq_endpoint_returns_the_catalog_for_a_known_section(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->getJson(route('support.faq', [
            'tenantBySlug' => $tenant->slug,
            'section' => 'reception',
        ]));

        $response->assertOk()
            ->assertJsonPath('faqs.0.id', 'reception-new-manual-ot');
    }

    public function test_faq_endpoint_returns_empty_list_for_unknown_section(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->getJson(route('support.faq', [
            'tenantBySlug' => $tenant->slug,
        ]));

        $response->assertOk()->assertExactJson(['faqs' => []]);
    }

    public function test_ask_endpoint_grounds_the_answer_in_the_matched_faq_selector(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        SupportAssistantAgent::fake([
            [
                'answer' => 'Presiona "Nueva Recepción" y completa el formulario.',
                'matched_faq_id' => 'reception-new-manual-ot',
            ],
        ])->preventStrayPrompts();

        $response = $this->actingAs($admin)->postJson(route('support.ask', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'section' => 'reception',
            'question' => '¿Cómo creo una OT manualmente?',
        ]);

        $response->assertOk()->assertJson([
            'answer' => 'Presiona "Nueva Recepción" y completa el formulario.',
            'selector' => '[data-tour="reception-new-entry"]',
            'matched' => true,
        ]);
    }

    public function test_ask_endpoint_rejects_an_unknown_section(): void
    {
        $tenant = $this->setUpTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->postJson(route('support.ask', [
            'tenantBySlug' => $tenant->slug,
        ]), [
            'section' => 'not-a-real-section',
            'question' => 'hola',
        ]);

        $response->assertStatus(422);
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
