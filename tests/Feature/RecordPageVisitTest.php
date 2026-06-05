<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordPageVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['analytics.track_testing_visits' => true]);
    }

    public function test_page_visit_records_total_visits(): void
    {
        $this->withoutVite();

        $this->assertEquals(0, PageVisit::count());

        $response = $this->get('/');
        $response->assertOk();

        $this->assertEquals(1, PageVisit::count());
        $visit = PageVisit::first();
        $this->assertEquals('/', $visit->path);
        $this->assertEquals(1, $visit->visits);
        $this->assertEquals(1, $visit->unique_visits);

        // Second visit from same session should increment visits but NOT unique_visits
        $response = $this->get('/');
        $response->assertOk();

        $visit->refresh();
        $this->assertEquals(2, $visit->visits);
        $this->assertEquals(1, $visit->unique_visits);
    }

    public function test_page_visit_records_multiple_paths_as_unique(): void
    {
        $this->withoutVite();

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
        $this->withoutVite();

        // Session 1
        $this->get('/');

        // Session 2
        $this->flushSession();
        $this->get('/');

        $visit = PageVisit::first();
        $this->assertEquals(2, $visit->visits);
        $this->assertEquals(2, $visit->unique_visits);
    }
}
