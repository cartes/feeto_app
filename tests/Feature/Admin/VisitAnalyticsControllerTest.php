<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\PageVisit;
use App\Models\PageVisitVisitor;
use App\Models\Tenant;
use App\Models\User;
use App\Services\VisitAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_and_regular_users_cannot_access_visit_analytics(): void
    {
        $this->get(route('admin.analytics.visits'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.analytics.visits'))->assertForbidden();
    }

    public function test_super_admin_sees_the_full_report(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create(['name' => 'Taller Centro', 'slug' => 'taller-centro']);

        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        PageVisit::create(['path' => '/', 'date' => $today, 'scope' => 'site', 'visits' => 10, 'unique_visits' => 6]);
        PageVisit::create(['path' => 'precios', 'date' => $yesterday, 'scope' => 'site', 'visits' => 4, 'unique_visits' => 3]);
        PageVisit::create(['path' => 'taller/taller-centro', 'date' => $today, 'scope' => 'tenant', 'tenant_id' => $tenant->id, 'visits' => 5, 'unique_visits' => 4]);
        PageVisit::create(['path' => 'taller/taller-centro/dashboard', 'date' => $today, 'scope' => 'app', 'tenant_id' => $tenant->id, 'visits' => 50, 'unique_visits' => 2]);

        // Mismo visitante dos días distintos: 1 único, 2 "días de visitante".
        PageVisitVisitor::create(['date' => $today, 'visitor_hash' => str_repeat('a', 40), 'scope' => 'site', 'device' => 'desktop', 'referrer' => 'google.com', 'entry_path' => '/', 'page_views' => 6]);
        PageVisitVisitor::create(['date' => $yesterday, 'visitor_hash' => str_repeat('a', 40), 'scope' => 'site', 'device' => 'desktop', 'referrer' => 'google.com', 'entry_path' => 'precios', 'page_views' => 4]);
        PageVisitVisitor::create(['date' => $today, 'visitor_hash' => str_repeat('b', 40), 'scope' => 'site', 'device' => 'mobile', 'referrer' => null, 'entry_path' => '/', 'page_views' => 4]);
        PageVisitVisitor::create(['date' => $today, 'visitor_hash' => str_repeat('c', 40), 'scope' => 'tenant', 'tenant_id' => $tenant->id, 'device' => 'mobile', 'referrer' => 'instagram', 'entry_path' => 'taller/taller-centro', 'page_views' => 5]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.visits', ['period' => '7d', 'scope' => 'site']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Visits')
                ->where('report.period', '7d')
                ->where('report.scope', 'site')
                ->where('report.summary.visits', 14)
                ->where('report.summary.unique_visitors', 2)
                ->where('report.summary.visitor_days', 3)
                ->where('report.summary.pages_per_visitor', 4.7)
                ->has('report.by_day', 7)
                ->where('report.by_day.6.visits', 10)
                ->where('report.by_day.6.unique_visitors', 2)
                ->has('report.top_pages', 2)
                ->where('report.top_pages.0.path', '/')
                ->where('report.referrers.0.source', 'google.com')
                ->where('report.referrers.0.visitors', 2)
                ->has('report.devices', 3)
                ->has('report.by_weekday', 7)
                ->has('report.by_scope', 3)
                ->where('report.by_scope.2.visits', 50)
                ->has('report.by_tenant', 1)
                ->where('report.by_tenant.0.name', 'Taller Centro')
                ->where('report.by_tenant.0.visits', 5)
                ->where('report.by_tenant.0.unique_visitors', 1)
            );
    }

    public function test_invalid_period_and_scope_fall_back_to_defaults(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.analytics.visits', ['period' => 'nope', 'scope' => 'weird']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.period', '30d')
                ->where('report.scope', 'all')
                ->has('report.by_day', 30)
            );
    }

    public function test_dashboard_snapshot_defaults_to_site_scope_and_compares_with_previous_period(): void
    {
        $admin = User::factory()->superAdmin()->create();

        PageVisit::create(['path' => '/', 'date' => today()->toDateString(), 'scope' => 'site', 'visits' => 8, 'unique_visits' => 5]);
        PageVisit::create(['path' => '/', 'date' => today()->subDays(8)->toDateString(), 'scope' => 'site', 'visits' => 4, 'unique_visits' => 3]);
        PageVisit::create(['path' => 'taller/x/dashboard', 'date' => today()->toDateString(), 'scope' => 'app', 'visits' => 99, 'unique_visits' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => '7d']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('visits.scope', 'site')
                ->where('visits.period', '7d')
                ->where('visits.summary.visits', 8)
                ->where('visits.summary.previous.visits', 4)
                ->where('visits.summary.change.visits', 100)
                ->has('visits.by_day', 7)
            );
    }

    public function test_unique_visitors_are_null_for_days_before_tracking_started(): void
    {
        $service = app(VisitAnalyticsService::class);

        PageVisit::create(['path' => '/', 'date' => today()->subDays(2)->toDateString(), 'scope' => 'site', 'visits' => 3, 'unique_visits' => 3]);
        PageVisit::create(['path' => '/', 'date' => today()->toDateString(), 'scope' => 'site', 'visits' => 2, 'unique_visits' => 2]);
        PageVisitVisitor::create(['date' => today()->toDateString(), 'visitor_hash' => str_repeat('d', 40), 'scope' => 'site', 'entry_path' => '/', 'page_views' => 2]);

        $range = $service->range('7d');
        $byDay = $service->byDay($range['from'], $range['to'], 'site');

        $this->assertNull($byDay[4]['unique_visitors']);
        $this->assertSame(3, $byDay[4]['visits']);
        $this->assertSame(1, $byDay[6]['unique_visitors']);
    }
}
