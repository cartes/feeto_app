<?php

namespace Tests\Feature\TenantFinder;

use App\Models\Tenant;
use App\TenantFinder\PathOrDomainTenantFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PathOrDomainTenantFinderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_tenant_from_taller_slug_path_before_domain(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'medelauto',
            'domain' => 'medelauto.test',
        ]);

        Tenant::factory()->create([
            'slug' => 'other',
            'domain' => '127.0.0.1',
        ]);

        $request = Request::create('http://127.0.0.1:8000/taller/medelauto/dashboard');

        $resolvedTenant = app(PathOrDomainTenantFinder::class)->findForRequest($request);

        $this->assertSame($tenant->id, $resolvedTenant?->getKey());
    }

    public function test_it_falls_back_to_domain_when_path_does_not_contain_taller_slug(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'medelauto.test',
        ]);

        $request = Request::create('http://medelauto.test/dashboard');

        $resolvedTenant = app(PathOrDomainTenantFinder::class)->findForRequest($request);

        $this->assertSame($tenant->id, $resolvedTenant?->getKey());
    }
}
