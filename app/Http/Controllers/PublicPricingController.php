<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanFeatureService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PublicPricingController extends Controller
{
    public function __invoke(PlanFeatureService $planFeatureService): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_annual' => $plan->price_annual,
                'discounted_monthly_price' => $plan->discountedMonthlyPrice(),
                'features' => array_values(array_unique(array_merge(
                    $plan->features ?? [],
                    collect($plan->feature_keys ?? [])
                        ->map(fn (string $key): string => $planFeatureService->definition($key)['label'])
                        ->all(),
                ))),
                'max_users' => $plan->max_users,
                'max_branches' => $plan->max_branches,
                'is_popular' => (bool) $plan->is_popular,
                'has_discount' => $plan->hasActiveDiscount(),
                'discount_percent' => $plan->discount_percent,
                'trial_days' => $plan->trial_days,
            ]);

        return Inertia::render('Pricing', [
            'plans' => $plans,
            'canLogin' => Route::has('login'),
            'seo' => $this->resolveMarketingSeo('pricing'),
            'christmasOffer' => [
                'active' => true,
                'offer_price' => 19990,
                'deadline_date' => '2026-12-31T23:59:59',
                'deadline_label' => '31 de diciembre',
                'duration_label' => '1 año completo',
                'target_plan_slug' => 'empresa',
                'title' => 'Oferta Navideña TallerFlow',
                'description' => 'Todos los talleres que contraten hasta el 31 de diciembre tendrán todas las funcionalidades por 1 año a $19.990.',
            ],
        ]);
    }
}
