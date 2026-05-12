<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSharedPropsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_shared_props_only_load_tenant_feature_context_once(): void
    {
        $plan = Plan::factory()->create([
            'slug' => 'empresa-test',
            'name' => 'Empresa Test',
            'max_users' => 20,
            'feature_keys' => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
                PlanFeatureService::FEATURE_AUTO_WHATSAPP,
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                PlanFeatureService::FEATURE_COMMERCIAL_REPORTS,
                PlanFeatureService::FEATURE_CUSTOM_ROLES,
                PlanFeatureService::FEATURE_SEO_MANAGER,
            ],
        ]);

        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'plan' => 'empresa',
            'plan_type' => 'empresa',
        ]);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $this->actingAs($admin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('taller.dashboard', ['tenantBySlug' => $tenant->slug]));

        $response->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query');
        $tenantFeatureQueries = $queries->filter(
            static fn (string $query): bool => Str::contains($query, 'tenant_features')
        );
        $planQueries = $queries->filter(
            static fn (string $query): bool => Str::contains($query, 'plans')
        );

        $this->assertLessThanOrEqual(
            1,
            $tenantFeatureQueries->count(),
            'Expected a single tenant feature query, got: '.$tenantFeatureQueries->implode(' | ')
        );

        $this->assertLessThanOrEqual(
            1,
            $planQueries->count(),
            'Expected a single plan query, got: '.$planQueries->implode(' | ')
        );

        $this->assertLessThanOrEqual(
            20,
            $queries->count(),
            'Expected dashboard shared props to stay under 20 queries, got '.$queries->count()
        );
    }
}
