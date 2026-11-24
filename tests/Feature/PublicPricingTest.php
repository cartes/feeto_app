<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_renders_with_christmas_offer(): void
    {
        Plan::create([
            'name' => 'Empresa',
            'slug' => 'empresa',
            'description' => 'Plan full con todas las funcionalidades.',
            'price_monthly' => 79990,
            'price_annual' => 799900,
            'max_users' => 20,
            'max_branches' => 3,
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 1,
            'features' => ['IA de patentes', 'WhatsApp automático', 'Agenda', 'Inventario'],
        ]);

        $response = $this->get('/precios');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->has('plans', 1)
                ->where('plans.0.name', 'Empresa')
                ->has('christmasOffer')
                ->where('christmasOffer.active', true)
                ->where('christmasOffer.offer_price', 19990)
                ->where('christmasOffer.deadline_label', '31 de diciembre')
                ->where('christmasOffer.target_plan_slug', 'empresa')
            );
    }
}
