<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);

        parent::tearDown();
    }

    public function test_it_forces_https_urls_in_production(): void
    {
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/'));
    }

    public function test_it_does_not_force_https_urls_outside_production(): void
    {
        $this->app['env'] = 'testing';

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', URL::to('/'));
    }
}
