<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\PageVisitVisitor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordPageVisitTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    private const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();
        config(['analytics.track_testing_visits' => true]);
        $this->withoutVite();
    }

    public function test_page_visit_records_total_visits(): void
    {
        $this->assertEquals(0, PageVisit::count());

        $this->get('/')->assertOk();

        $this->assertEquals(1, PageVisit::count());
        $visit = PageVisit::first();
        $this->assertEquals('/', $visit->path);
        $this->assertEquals(PageVisit::SCOPE_SITE, $visit->scope);
        $this->assertEquals(1, $visit->visits);
        $this->assertEquals(1, $visit->unique_visits);

        // Second visit from same session should increment visits but NOT unique_visits
        $this->get('/')->assertOk();

        $visit->refresh();
        $this->assertEquals(2, $visit->visits);
        $this->assertEquals(1, $visit->unique_visits);
    }

    public function test_page_visit_records_multiple_paths_as_unique(): void
    {
        $this->get('/');
        $this->get('/precios');

        $this->assertEquals(2, PageVisit::count());

        $homeVisit = PageVisit::where('path', '/')->first();
        $preciosVisit = PageVisit::where('path', 'precios')->first();

        $this->assertNotNull($homeVisit);
        $this->assertNotNull($preciosVisit);

        $this->assertEquals(1, $homeVisit->visits);
        $this->assertEquals(1, $homeVisit->unique_visits);

        $this->assertEquals(1, $preciosVisit->visits);
        $this->assertEquals(1, $preciosVisit->unique_visits);
    }

    public function test_different_sessions_increment_unique_visits(): void
    {
        // Session 1
        $this->get('/');

        // Session 2
        $this->flushSession();
        $this->get('/');

        $visit = PageVisit::first();
        $this->assertEquals(2, $visit->visits);
        $this->assertEquals(2, $visit->unique_visits);
    }

    public function test_public_tenant_landing_visit_is_attributed_to_the_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Taller Norte',
            'slug' => 'taller-norte',
        ]);

        $this->get(route('taller.landing', ['tenantBySlug' => $tenant->slug]))
            ->assertOk();

        $this->assertDatabaseHas('page_visits', [
            'tenant_id' => $tenant->id,
            'scope' => PageVisit::SCOPE_TENANT,
            'path' => 'taller/taller-norte',
            'visits' => 1,
            'unique_visits' => 1,
        ]);

        $this->assertDatabaseHas('page_visit_visitors', [
            'tenant_id' => $tenant->id,
            'scope' => PageVisit::SCOPE_TENANT,
            'entry_path' => 'taller/taller-norte',
            'page_views' => 1,
        ]);
    }

    public function test_same_visitor_counts_once_per_day_across_pages(): void
    {
        $headers = ['User-Agent' => self::CHROME_UA];

        $this->withHeaders($headers)->get('/');
        $this->withHeaders($headers)->get('/precios');
        $this->withHeaders($headers)->get('/');

        $this->assertEquals(3, (int) PageVisit::sum('visits'));
        $this->assertEquals(1, PageVisitVisitor::count());

        $visitor = PageVisitVisitor::first();
        $this->assertEquals(3, $visitor->page_views);
        $this->assertEquals('/', $visitor->entry_path);
        $this->assertEquals('desktop', $visitor->device);
        $this->assertNull($visitor->referrer);
    }

    public function test_different_browsers_are_different_visitors(): void
    {
        $this->withHeaders(['User-Agent' => self::CHROME_UA])->get('/');
        $this->withHeaders(['User-Agent' => self::IPHONE_UA])->get('/');

        $this->assertEquals(2, PageVisitVisitor::count());
        $this->assertEquals(1, PageVisitVisitor::where('device', 'mobile')->count());
        $this->assertEquals(1, PageVisitVisitor::where('device', 'desktop')->count());
    }

    public function test_external_referrer_and_utm_source_are_stored(): void
    {
        $this->withHeaders(['User-Agent' => self::CHROME_UA, 'Referer' => 'https://www.google.com/search?q=taller'])
            ->get('/');

        $this->assertDatabaseHas('page_visit_visitors', ['referrer' => 'google.com']);

        $this->withHeaders(['User-Agent' => self::IPHONE_UA])
            ->get('/?utm_source=Instagram');

        $this->assertDatabaseHas('page_visit_visitors', ['referrer' => 'instagram']);
    }

    public function test_internal_referrer_is_ignored(): void
    {
        $this->withHeaders(['User-Agent' => self::CHROME_UA, 'Referer' => 'http://localhost/precios'])
            ->get('/');

        $this->assertDatabaseHas('page_visit_visitors', ['referrer' => null]);
    }

    public function test_bots_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->get('/')
            ->assertOk();

        $this->assertEquals(0, PageVisit::count());
        $this->assertEquals(0, PageVisitVisitor::count());
    }

    public function test_json_and_prefetch_requests_are_not_counted(): void
    {
        $this->getJson('/');
        $this->withHeaders(['Purpose' => 'prefetch'])->get('/');
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '', 'X-Inertia-Partial-Component' => 'Public/Home', 'X-Inertia-Partial-Data' => 'foo'])->get('/');

        $this->assertEquals(0, PageVisit::count());
    }

    public function test_super_admin_navigation_is_not_counted(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/')->assertOk();

        $this->assertEquals(0, PageVisit::count());
    }

    public function test_authenticated_tenant_user_counts_as_app_scope(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'taller-sur']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/precios')->assertOk();

        $this->assertDatabaseHas('page_visits', [
            'path' => 'precios',
            'scope' => PageVisit::SCOPE_APP,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('page_visit_visitors', [
            'scope' => PageVisit::SCOPE_APP,
            'tenant_id' => $tenant->id,
        ]);
    }
}
